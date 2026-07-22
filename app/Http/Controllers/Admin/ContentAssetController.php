<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use App\Services\ContentAssetSynchronizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Keystone\Toolkit\Forms\Form;
use Keystone\Toolkit\Services\KeystoneApiService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ContentAssetController extends AdminController
{
    public function index(): View
    {
        app(ContentAssetSynchronizer::class)->syncForUser((int) auth()->id());
        page()->setTitle('Content Intake');

        return view('admin.content.index', [
            'assets' => ContentAsset::latest()->paginate(20),
            'assetTypes' => ContentAsset::types(),
            'uploadForm' => $this->uploadForm(),
        ]);
    }

    public function review(): View
    {
        app(ContentAssetSynchronizer::class)->syncForUser((int) auth()->id());

        page()->setTitle('Review Uploads');
        $assets = ContentAsset::latest()->get();

        return view('admin.content.review', [
            'assets' => $assets,
            'assetTypes' => ContentAsset::types(),
            'reviewForm' => $this->reviewForm($assets),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = ContentAsset::rules();
        $rules['type'][] = 'in:'.implode(',', array_keys(ContentAsset::types()));
        $rules['asset'] = [
            'required',
            'file',
            'max:25600',
            'mimes:jpg,jpeg,png,webp,gif,pdf,docx,xls,xlsx,csv,txt,rtf',
        ];

        $validated = $request->validate($rules);

        $file = $request->file('asset');
        $stored = $request->user()->uploadPrivate($file, 'business-assets');

        $asset = ContentAsset::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_request_id' => (string) Str::uuid(),
        ]);

        $this->submitAsset($asset, $file);

        return redirect()
            ->route('admin.content.index')
            ->with('status', 'Asset uploaded and queued for content analysis.');
    }

    /**
     * Drag-and-drop auto-upload: stores a dropped file with sensible defaults and queues it for AI
     * review without the full form. Type defaults to 'other'; the ingestion pass can refine it.
     */
    public function dropUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:25600',
                'mimes:jpg,jpeg,png,webp,gif,pdf,docx,xls,xlsx,csv,txt,rtf',
            ],
        ]);

        $file = $validated['file'];
        $stored = $request->user()->uploadPrivate($file, 'business-assets');

        $asset = ContentAsset::create([
            'user_id' => $request->user()->id,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => 'other',
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_request_id' => (string) Str::uuid(),
        ]);

        $this->submitAsset($asset, $file);

        return response()->json([
            'ok' => true,
            'asset' => [
                'id' => $asset->id,
                'title' => $asset->title,
                'filename' => $asset->original_filename,
                'size' => $asset->formatted_size,
                'status' => $asset->ingestion_status,
            ],
        ]);
    }

    public function download(ContentAsset $contentAsset): StreamedResponse
    {
        abort_unless($contentAsset->user_id === auth()->id(), 404);

        return Storage::disk($contentAsset->disk)->download(
            $contentAsset->path,
            $contentAsset->original_filename
        );
    }

    private function submitAsset(ContentAsset $asset, UploadedFile $file): void
    {
        try {
            $response = app(KeystoneApiService::class)->uploadAsset(
                $file,
                $asset->id,
                $asset->type,
                $asset->title,
                $asset->notes,
                $asset->remote_request_id,
            );
            $remoteId = data_get($response, 'asset.id');

            if (! is_string($remoteId) || $remoteId === '') {
                throw new \RuntimeException('Kirby Creative returned an invalid asset resource.');
            }

            $asset->update([
                'remote_id' => $remoteId,
                'remote_status' => data_get($response, 'asset.status', 'queued'),
                'remote_error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $asset->update([
                'remote_status' => ContentAsset::STATUS_FAILED,
                'remote_error' => $exception->getMessage(),
            ]);
        }
    }

    private function uploadForm(): Form
    {

        page()->setTitle('Content Intake');

        return (new Form)
            ->setAction(route('admin.content.store'))
            ->setAttributes(['class' => 'margin:top:1 flex:column gap:1'])
            ->setSubmit('Upload and queue asset', [
                'class' => 'btn btn--primary w:100',
            ])
            ->setSchema([
                'form' => [
                    'title' => [
                        'label' => 'Title',
                        'placeholder' => 'Summer menu, holiday ad, storefront photo',
                    ],
                    'type' => [
                        'type' => 'select',
                        'label' => 'Asset type',
                        'options' => array_flip(ContentAsset::types()),
                        'attributes' => ['required' => true],
                    ],
                    'asset' => [
                        'type' => 'file',
                        'label' => 'File',
                        'help' => 'PDF, image, Word, Excel, CSV, text, or RTF. Max 25 MB.',
                        'attributes' => [
                            'required' => true,
                            'class' => 'admin-field admin-file margin:top:0o5',
                        ],
                    ],
                    'notes' => [
                        'type' => 'textarea',
                        'label' => 'Notes for ingestion',
                        'placeholder' => 'What should the site builder learn from this?',
                        'attributes' => ['rows' => 5],
                    ],
                ],
            ]);
    }

    private function reviewForm($assets): Form
    {
        page()->setTitle('Review Uploads');
        $selectedAssetIds = array_map('intval', (array) old('asset_ids', $assets->modelKeys()));
        $fields = [
            'reviewed' => [
                'type' => 'hidden',
                'value' => '1',
            ],
        ];

        foreach ($assets as $asset) {
            $fields['asset_'.$asset->id] = [
                'view' => 'admin.content.partials.review-asset-field',
                'type' => 'checkbox',
                'name' => 'asset_ids[]',
                'value' => $asset->id,
                'checked' => in_array((int) $asset->id, $selectedAssetIds, true),
                'asset' => $asset,
                'assetTypes' => ContentAsset::types(),
            ];
        }

        return (new Form)
            ->setAction(route('admin.page-suggestions.generate'))
            ->setAttributes(['class' => 'margin:top:2'])
            ->setSubmit('Generate suggestions', [
                'class' => 'btn btn--primary',
            ])
            ->setSchema(['form' => $fields]);
    }
}
