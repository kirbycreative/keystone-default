<?php

namespace App\Services;

use App\Models\PageSuggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Keystone\Toolkit\Services\OpenRouterService;

/**
 * Turns a client's selected business assets into a suggested site map via OpenRouter.
 * Returns the proposed pages plus the model that produced them, so feedback can attribute
 * strikes to the right model.
 */
class SiteMapGenerator
{
    public function __construct(private OpenRouterService $openRouter)
    {
    }

    /**
     * @param  Collection<int, \App\Models\ContentAsset>  $assets
     * @return array{model: ?string, pages: array<int, array{title: string, slug: string, parent_slug: ?string, summary: string, rationale: string, sections: list<string>}>}
     */
    public function generate(Collection $assets): array
    {
        $result = $this->openRouter->request([
            'task' => PageSuggestion::TASK_SITEMAP,
            'response_type' => 'json_object',
            'directives' => 'You are a senior website strategist and information architect for small-business '
                .'marketing sites. From the client\'s uploaded materials, propose a concise, conversion-focused '
                .'site map. Return valid JSON only.',
            'prompt' => $this->prompt($assets),
            'temperature' => 0.3,
        ], static fn ($response): bool => is_array($response)
            && isset($response['pages'])
            && is_array($response['pages'])
            && $response['pages'] !== []
        );

        return [
            'model' => $this->openRouter->lastModel,
            'pages' => $this->normalizePages($result['pages']),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\ContentAsset>  $assets
     */
    private function prompt(Collection $assets): string
    {
        $lines = ['Business materials uploaded by the client:'];

        foreach ($assets->values() as $index => $asset) {
            $label = $asset->title ?: $asset->original_filename ?: 'Untitled asset';
            $lines[] = sprintf('%d. [%s] %s', $index + 1, $asset->type, $label);

            if ($asset->notes) {
                $lines[] = '   Notes: '.$asset->notes;
            }

            if (filled($asset->ingestion_result)) {
                $lines[] = '   Extracted: '.Str::limit((string) json_encode($asset->ingestion_result), 600);
            }
        }

        $lines[] = '';
        $lines[] = 'Return JSON with this exact shape:';
        $lines[] = '{"pages":[{"title":string,"slug":string,"parent_slug":string|null,"summary":string,'
            .'"rationale":string,"sections":string[]}]}';
        $lines[] = 'Rules:';
        $lines[] = '- Include exactly one home page with slug "/" and parent_slug null.';
        $lines[] = '- Produce 4 to 7 pages total, grounded in the actual materials above.';
        $lines[] = '- Non-home slugs are lowercase, hyphenated, no leading slash (e.g. "about", "menu").';
        $lines[] = '- parent_slug references the slug of another page, or null for top-level pages.';
        $lines[] = '- summary is 1-2 sentences; rationale explains why the page belongs on the site.';
        $lines[] = '- sections lists the suggested content blocks for the page.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, mixed>  $pages
     * @return array<int, array{title: string, slug: string, parent_slug: ?string, summary: string, rationale: string, sections: list<string>}>
     */
    private function normalizePages(array $pages): array
    {
        $normalized = [];
        $seen = [];

        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            $title = trim((string) ($page['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $slug = $this->normalizeSlug((string) ($page['slug'] ?? ''), $title);

            if (isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;

            $parentRaw = $page['parent_slug'] ?? null;
            $parent = ($parentRaw === null || $parentRaw === '')
                ? null
                : $this->normalizeSlug((string) $parentRaw, (string) $parentRaw);

            $normalized[] = [
                'title' => $title,
                'slug' => $slug,
                'parent_slug' => $slug === '/' ? null : $parent,
                'summary' => trim((string) ($page['summary'] ?? '')) ?: $title,
                'rationale' => trim((string) ($page['rationale'] ?? '')),
                'sections' => array_values(array_filter(
                    array_map(static fn ($section): string => trim((string) $section), (array) ($page['sections'] ?? [])),
                    static fn (string $section): bool => $section !== '',
                )),
            ];
        }

        if (! isset($seen['/'])) {
            array_unshift($normalized, [
                'title' => 'Home',
                'slug' => '/',
                'parent_slug' => null,
                'summary' => 'Primary landing page that introduces the business and directs visitors to the most important actions.',
                'rationale' => 'Every site needs one clear entry point for positioning, offers, and navigation.',
                'sections' => ['Hero', 'Highlights', 'Call to action'],
            ]);
        }

        return $normalized;
    }

    private function normalizeSlug(string $slug, string $fallback): string
    {
        if (trim($slug) === '/') {
            return '/';
        }

        $clean = Str::slug($slug);

        return $clean !== '' ? $clean : Str::slug($fallback);
    }
}
