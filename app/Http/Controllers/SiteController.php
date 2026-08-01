<?php

namespace App\Http\Controllers;

use App\Services\CanonicalSiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Keystone\Admin\Services\KeystoneApiService;
use RuntimeException;

class SiteController extends Controller
{
    public function public(
        Request $request,
        KeystoneApiService $api,
        CanonicalSiteRenderer $renderer,
        ?string $path = null,
    ): View|RedirectResponse {
        try {
            $version = $this->published($api);
        } catch (RuntimeException $exception) {
            if ($this->siteHasNotBeenPublished($exception)) {
                return redirect()->route('login');
            }

            throw $exception;
        }

        if ($redirect = $this->canonicalRedirect($request, $version)) {
            return $redirect;
        }

        return $this->view($version, $renderer, false, '/'.ltrim($path ?? '', '/'));
    }

    public function sitemap(KeystoneApiService $api, CanonicalSiteRenderer $renderer): Response
    {
        $version = $this->published($api);
        abort_unless(data_get($version, 'document.seo.sitemap_enabled', true), 404);
        $primary = $this->primaryUrl($version);
        $urls = array_map(
            fn (array $page): string => $primary.($page['path'] === '/' ? '' : $page['path']),
            $renderer->sitemapPages($version),
        );
        $xml = view('site.sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(KeystoneApiService $api): Response
    {
        $version = $this->published($api);
        $robots = (string) data_get($version, 'document.seo.robots', "User-agent: *\nAllow: /");
        if (data_get($version, 'document.seo.sitemap_enabled', true)) {
            $robots .= "\nSitemap: ".$this->primaryUrl($version).'/sitemap.xml';
        }

        return response(trim($robots)."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function preview(KeystoneApiService $api, CanonicalSiteRenderer $renderer): View
    {
        return $this->view(
            data_get($api->siteSchemaDraft(), 'site_version', []),
            $renderer,
            true,
        );
    }

    private function view(array $version, CanonicalSiteRenderer $renderer, bool $preview, string $path = '/'): View
    {
        try {
            $page = $renderer->page($version, $path);
        } catch (RuntimeException) {
            abort(404);
        }
        $canonicalUrl = $this->primaryUrl($version).($page['path'] === '/' ? '' : $page['path']);

        return view('site.page', [
            'siteVersion' => $version,
            'document' => $version['document'] ?? [],
            'page' => $page,
            'metadata' => $renderer->metadata($version, $page, $canonicalUrl),
            'preview' => $preview,
        ]);
    }

    private function published(KeystoneApiService $api): array
    {
        return Cache::remember(
            'canonical-site.published',
            now()->addMinutes(5),
            fn (): array => data_get($api->siteSchemaPublished(), 'site_version', []),
        );
    }

    private function siteHasNotBeenPublished(RuntimeException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Kirby Creative API request failed (404):')
            && str_contains($exception->getMessage(), 'This site has not been published.');
    }

    private function primaryUrl(array $version): string
    {
        $primary = data_get($version, 'document.domains.primary');

        return $primary ? 'https://'.trim($primary, '/') : rtrim(config('app.url'), '/');
    }

    private function canonicalRedirect(Request $request, array $version): ?RedirectResponse
    {
        $primary = data_get($version, 'document.domains.primary');
        $redirects = data_get($version, 'document.domains.redirects', []);
        if (! $primary || ! in_array($request->getHost(), $redirects, true)) {
            return null;
        }

        $target = 'https://'.$primary.'/'.$request->path();
        if ($request->getQueryString()) {
            $target .= '?'.$request->getQueryString();
        }

        return redirect()->away($target, 301);
    }
}
