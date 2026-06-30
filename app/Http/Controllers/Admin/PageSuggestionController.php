<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use App\Models\PageSuggestion;
use App\Services\SiteMapGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Keystone\Toolkit\Forms\Form;
use Keystone\Toolkit\Services\OpenRouterService;
use RuntimeException;

class PageSuggestionController extends AdminController
{
    public function index(Request $request): View
    {
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

        try {
            $generated = app(SiteMapGenerator::class)->generate($assets);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()
                ->route('admin.content.review')
                ->withErrors(['asset_ids' => 'AI site map generation failed. Please try again. (' . $exception->getMessage() . ')']);
        }

        $userId = $request->user()->id;
        $assetIds = $assets->modelKeys();
        $model = $generated['model'];

        // First pass: top-level pages, so children can resolve their parent by slug.
        $idBySlug = [];
        $sortOrder = 10;

        foreach ($generated['pages'] as $page) {
            if ($page['parent_slug'] !== null) {
                continue;
            }

            $suggestion = $this->upsertSuggestion($userId, null, $page, $assetIds, $model, $sortOrder);
            $idBySlug[$page['slug']] = $suggestion->id;
            $sortOrder += 10;
        }

        $homeId = $idBySlug['/'] ?? (reset($idBySlug) ?: null);

        // Second pass: child pages attach to their named parent, falling back to home.
        foreach ($generated['pages'] as $page) {
            if ($page['parent_slug'] === null) {
                continue;
            }

            $parentId = $idBySlug[$page['parent_slug']] ?? $homeId;
            $this->upsertSuggestion($userId, $parentId, $page, $assetIds, $model, $sortOrder);
            $sortOrder += 10;
        }

        return redirect()
            ->route('admin.page-suggestions.index')
            ->with('status', 'Page suggestions generated from the reviewed uploads.');
    }

    public function feedback(Request $request, PageSuggestion $pageSuggestion): RedirectResponse
    {
        abort_unless($pageSuggestion->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'feedback' => ['required', 'in:yes,no'],
        ]);

        $positive = $validated['feedback'] === 'yes';

        $pageSuggestion->update([
            'ai_feedback' => $positive,
            'ai_feedback_at' => now(),
        ]);

        // A "No" on an AI-produced page is the human adequacy signal: strike the model for this task.
        if (! $positive && $pageSuggestion->ai_model) {
            app(OpenRouterService::class)->recordStrike(
                $pageSuggestion->ai_task ?? PageSuggestion::TASK_SITEMAP,
                $pageSuggestion->ai_model,
                'Client marked page "' . $pageSuggestion->title . '" as not meeting expectations.',
            );
        }

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
