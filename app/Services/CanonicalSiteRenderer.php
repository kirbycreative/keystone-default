<?php

namespace App\Services;

use Keystone\Admin\Contracts\ValidatesSitePublication;
use RuntimeException;

class CanonicalSiteRenderer implements ValidatesSitePublication
{
    /** @return array<string, mixed> */
    public function rootPage(array $siteVersion): array
    {
        return $this->page($siteVersion, '/');
    }

    /** @return array<string, mixed> */
    public function page(array $siteVersion, string $path): array
    {
        $pages = data_get($siteVersion, 'document.pages');
        if (! is_array($pages)) {
            throw new RuntimeException('The canonical site version has no page collection.');
        }

        $normalizedPath = '/'.trim($path, '/');
        $normalizedPath = $normalizedPath === '/' ? '/' : rtrim($normalizedPath, '/');
        $matches = array_values(array_filter(
            $pages,
            fn ($page): bool => is_array($page)
                && rtrim((string) ($page['path'] ?? ''), '/') === rtrim($normalizedPath, '/')
                && ($page['status'] ?? null) === 'enabled',
        ));
        if (count($matches) !== 1) {
            throw new RuntimeException("The canonical site version must contain exactly one enabled page for {$normalizedPath}.");
        }

        return $matches[0];
    }

    /** @return array<int, array<string, mixed>> */
    public function sitemapPages(array $siteVersion): array
    {
        return array_values(array_filter(
            data_get($siteVersion, 'document.pages', []),
            fn ($page): bool => is_array($page)
                && ($page['status'] ?? null) === 'enabled'
                && ! data_get($page, 'seo.noindex', false),
        ));
    }

    /** @return array<string, mixed> */
    public function metadata(array $siteVersion, array $page, string $canonicalUrl): array
    {
        $document = $siteVersion['document'] ?? [];
        $pageTitle = data_get($page, 'seo.title') ?: ($page['title'] ?? '');
        $template = data_get($document, 'seo.title_template', '%s');

        return [
            'title' => str_contains($template, '%s') ? sprintf($template, $pageTitle) : $pageTitle,
            'description' => data_get($page, 'seo.description')
                ?: data_get($document, 'seo.default_description'),
            'canonical_url' => data_get($page, 'seo.canonical_url') ?: $canonicalUrl,
            'open_graph' => array_replace(
                data_get($document, 'seo.default_open_graph', []),
                data_get($page, 'seo.open_graph', []),
            ),
            'structured_data' => array_values(array_filter(array_merge(
                data_get($document, 'seo.structured_data', []),
                data_get($page, 'seo.structured_data', []),
            ), 'is_array')),
            'noindex' => (bool) data_get($page, 'seo.noindex', false),
        ];
    }

    public function validateLaunch(array $siteVersion): void
    {
        $document = $siteVersion['document'] ?? [];
        $pages = data_get($document, 'pages', []);
        $enabledPaths = [];
        foreach ($pages as $page) {
            if (($page['status'] ?? null) !== 'enabled') {
                continue;
            }
            $path = $page['path'] ?? null;
            if (! is_string($path) || isset($enabledPaths[$path])) {
                throw new RuntimeException('Enabled canonical page paths must be present and unique.');
            }
            $enabledPaths[$path] = true;
        }

        $this->rootPage($siteVersion);
        if (! data_get($document, 'domains.primary')) {
            throw new RuntimeException('A primary domain is required before publication.');
        }

        $analytics = data_get($document, 'settings.features.analytics', []);
        if (($analytics['enabled'] ?? false)
            && (! in_array($analytics['provider'] ?? null, ['plausible', 'google-analytics'], true)
                || empty($analytics['measurement_id']))) {
            throw new RuntimeException('Enabled analytics requires a supported provider and measurement ID.');
        }
    }
}
