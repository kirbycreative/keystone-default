<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use App\Models\PageSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Keystone\Toolkit\Forms\Form;
use Keystone\Toolkit\Services\KeystoneApiService;
use RuntimeException;
use Illuminate\Support\Str;

class PageSuggestionController extends AdminController
{
    public function index(Request $request): View
    {
        $this->syncRemoteLayout($request);
        page()->setTitle('Page Suggestions');

        $suggestions = PageSuggestion::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        page()->setTitle('Page Suggestions');

        return view('admin.page-suggestions.index', [
            'suggestions' => $suggestions,
            'siteTree' => $this->buildSiteTree($suggestions),
            'reviewForms' => $this->reviewForms($suggestions),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reviewed' => ['nullable', 'boolean'],
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer'],
        ]);

        if ($request->boolean('reviewed') && empty($validated['asset_ids'])) {
            return redirect()
                ->route('admin.content.review')
                ->withErrors(['asset_ids' => 'Select at least one reviewed upload before generating page suggestions.']);
        }

        $assets = ContentAsset::query()
            ->where('user_id', $request->user()->id)
            ->when(
                isset($validated['asset_ids']),
                fn($query) => $query->whereIn('id', $validated['asset_ids'])
            )
            ->get();

        if ($assets->isEmpty()) {
            return redirect()
                ->route('admin.content.review')
                ->withErrors(['asset_ids' => 'Upload or select at least one asset before generating page suggestions.']);
        }

        $onboarding = $request->user()->onboardingState();
        $remoteAssetIds = $assets->pluck('remote_id')->filter()->values()->all();

        if (count($remoteAssetIds) !== $assets->count() || ! $onboarding->generation_remote_id) {
            return redirect()->route('admin.content.review')->withErrors([
                'asset_ids' => 'Wait for every selected upload and the initial site brief to finish syncing before generating suggestions.',
            ]);
        }

        $requestId = $onboarding->site_layout_request_id ?: (string) Str::uuid();
        $onboarding->update(['site_layout_request_id' => $requestId, 'site_layout_status' => 'submitting', 'site_layout_error' => null]);

        try {
            $response = app(KeystoneApiService::class)->createSiteLayout([
                'schema_version' => 1,
                'request_id' => $requestId,
                'asset_ids' => $remoteAssetIds,
                'base_submission_id' => $onboarding->generation_remote_id,
            ], $requestId);
            $remoteId = data_get($response, 'site_layout.id');

            if (! is_string($remoteId) || $remoteId === '') {
                throw new RuntimeException('Kirby Creative returned an invalid site-layout resource.');
            }
        } catch (\Throwable $exception) {
            report($exception);

            $onboarding->update(['site_layout_status' => 'failed', 'site_layout_error' => $exception->getMessage()]);

            return redirect()
                ->route('admin.content.review')
                ->withErrors(['asset_ids' => 'AI site map generation failed. Please try again. (' . $exception->getMessage() . ')']);
        }

        $onboarding->update([
            'site_layout_remote_id' => $remoteId,
            'site_layout_status' => data_get($response, 'site_layout.status', 'queued'),
            'site_layout_error' => null,
        ]);

        return redirect()
            ->route('admin.page-suggestions.index')
            ->with('status', 'Page suggestions are being generated from the reviewed uploads.');
    }

    public function feedback(Request $request, PageSuggestion $pageSuggestion): RedirectResponse
    {
        abort_unless($pageSuggestion->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'feedback' => ['required', 'in:yes,no'],
        ]);

        $positive = $validated['feedback'] === 'yes';

        $onboarding = $request->user()->onboardingState();
        $reason = $positive ? null : $pageSuggestion->rejection_feedback;

        abort_unless($onboarding->site_layout_remote_id, 409);

        if (! $positive && blank($reason)) {
            return back()->withErrors(['feedback' => 'Deny the page with an explanation before marking the suggestion unhelpful.']);
        }

        app(KeystoneApiService::class)->recordAiFeedback(
            'site_layout_page',
            $onboarding->site_layout_remote_id.':'.$pageSuggestion->slug,
            $positive,
            $reason,
        );

        $pageSuggestion->update([
            'ai_feedback' => $positive,
            'ai_feedback_at' => now(),
        ]);

        return back()->with('status', 'Thanks for the feedback.');
    }

    public function updateStatus(Request $request, PageSuggestion $pageSuggestion): RedirectResponse
    {
        abort_unless($pageSuggestion->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                PageSuggestion::STATUS_APPROVED,
                PageSuggestion::STATUS_REJECTED,
            ])],
            'rejection_feedback' => [
                $request->input('status') === PageSuggestion::STATUS_REJECTED ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $pageSuggestion->update([
            'status' => $validated['status'],
            'rejection_feedback' => $validated['status'] === PageSuggestion::STATUS_REJECTED
                ? $validated['rejection_feedback']
                : null,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.page-suggestions.index')
            ->with('status', $validated['status'] === PageSuggestion::STATUS_APPROVED
                ? 'Page suggestion approved.'
                : 'Page suggestion denied and feedback saved.');
    }

    /**
     * @param  array{title: string, slug: string, summary: string, rationale: string, sections: list<string>}  $page
     * @param  array<int, int>  $sourceAssetIds
     */
    private function upsertSuggestion(int $userId, ?int $parentId, array $page, array $sourceAssetIds, ?string $model, int $sortOrder): PageSuggestion
    {
        return PageSuggestion::updateOrCreate(
            [
                'user_id' => $userId,
                'slug' => $page['slug'],
            ],
            [
                'parent_id' => $parentId,
                'title' => $page['title'],
                'summary' => $page['summary'],
                'rationale' => $page['rationale'],
                'source_asset_ids' => array_values($sourceAssetIds),
                'suggested_copy' => ['sections' => $page['sections']],
                'status' => PageSuggestion::STATUS_SUGGESTED,
                'rejection_feedback' => null,
                'reviewed_at' => null,
                'sort_order' => $sortOrder,
                'ai_model' => $model,
                'ai_task' => PageSuggestion::TASK_SITEMAP,
                'ai_feedback' => null,
                'ai_feedback_at' => null,
            ]
        );
    }

    private function buildSiteTree(Collection $suggestions): Collection
    {
        return $suggestions
            ->whereNull('parent_id')
            ->values()
            ->map(function (PageSuggestion $suggestion) use ($suggestions): PageSuggestion {
                $suggestion->setRelation(
                    'children',
                    $suggestions->where('parent_id', $suggestion->id)->sortBy('sort_order')->values()
                );

                return $suggestion;
            });
    }

    private function syncRemoteLayout(Request $request): void
    {
        $onboarding = $request->user()->onboardingState();

        if (! $onboarding->site_layout_remote_id || in_array($onboarding->site_layout_status, ['completed', 'failed'], true)) {
            return;
        }

        try {
            $response = app(KeystoneApiService::class)->siteLayout($onboarding->site_layout_remote_id);
            $layout = data_get($response, 'site_layout', []);
            $onboarding->update([
                'site_layout_status' => $layout['status'] ?? $onboarding->site_layout_status,
                'site_layout_error' => data_get($layout, 'error.message'),
            ]);

            if (($layout['status'] ?? null) === 'completed' && is_array($layout['pages'] ?? null)) {
                $sortOrder = 10;
                foreach ($layout['pages'] as $page) {
                    $slug = ($page['slug'] ?? '/') === '/' ? '/' : Str::slug((string) ($page['slug'] ?? $page['title'] ?? 'page'));
                    $this->upsertSuggestion($request->user()->id, null, [
                        'title' => (string) ($page['title'] ?? Str::headline($slug)),
                        'slug' => $slug,
                        'summary' => (string) ($page['goal'] ?? ''),
                        'rationale' => (string) ($page['goal'] ?? ''),
                        'sections' => array_values(array_map(static fn ($section): string => (string) ($section['type'] ?? $section['key'] ?? ''), (array) ($page['sections'] ?? []))),
                    ], [], null, $sortOrder);
                    $sortOrder += 10;
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function reviewForms(Collection $suggestions): array
    {
        return $suggestions
            ->mapWithKeys(fn(PageSuggestion $suggestion): array => [
                $suggestion->id => [
                    'approve' => $this->statusForm($suggestion, PageSuggestion::STATUS_APPROVED),
                    'reject' => $this->statusForm($suggestion, PageSuggestion::STATUS_REJECTED),
                ],
            ])
            ->all();
    }

    private function statusForm(PageSuggestion $suggestion, string $status): Form
    {
        $isRejection = $status === PageSuggestion::STATUS_REJECTED;
        $fields = [
            'status' => [
                'type' => 'hidden',
                'value' => $status,
            ],
        ];

        if ($isRejection) {
            $fields['rejection_feedback'] = [
                'type' => 'textarea',
                'label' => 'Why was this denied?',
                'value' => old('rejection_feedback', $suggestion->rejection_feedback),
                'placeholder' => 'Explain what missed the mark so future content generation can improve.',
                'attributes' => ['required' => true, 'rows' => 4],
            ];
        }

        return (new Form())
            ->setAction(route('admin.page-suggestions.status', $suggestion))
            ->setMethod('PATCH')
            ->setAttributes(['class' => $isRejection ? 'margin:top:1 flex:column gap:1' : ''])
            ->setSubmit($isRejection ? 'Save denial feedback' : 'Approve', [
                'class' => $isRejection ? 'btn btn--danger' : 'btn btn--success',
            ])
            ->setSchema(['form' => $fields]);
    }
}
