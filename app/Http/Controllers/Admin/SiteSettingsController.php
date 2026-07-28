<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\CanonicalSiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Keystone\Toolkit\Services\KeystoneApiService;

class SiteSettingsController extends AdminController
{
    public function show(KeystoneApiService $api): View
    {
        page()->setTitle('Site settings');

        return view('admin.site-settings', [
            'siteVersion' => data_get($api->siteSchemaDraft(), 'site_version'),
            'publishedVersions' => data_get($api->siteSchemaVersions(), 'site_versions', []),
            'usage' => data_get($api->siteUsage(), 'usage', []),
        ]);
    }

    public function identity(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'locale' => ['required', 'string', 'max:35'],
            'timezone' => ['required', 'timezone:all'],
        ]);

        $checksum = $validated['checksum'];
        unset($validated['checksum']);
        $api->updateSiteSchema('identity.update', $validated, $checksum, (string) Str::ulid());

        return back()->with('status', 'Site identity saved.');
    }

    public function seo(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'title_template' => ['required', 'string', 'max:180'],
            'default_description' => ['nullable', 'string', 'max:500'],
            'sitemap_enabled' => ['nullable', 'boolean'],
        ]);

        $checksum = $validated['checksum'];
        unset($validated['checksum']);
        $validated['sitemap_enabled'] = (bool) ($validated['sitemap_enabled'] ?? false);
        $api->updateSiteSchema('seo.update', $validated, $checksum, (string) Str::ulid());

        return back()->with('status', 'Search settings saved.');
    }

    public function publish(
        Request $request,
        KeystoneApiService $api,
        CanonicalSiteRenderer $renderer,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'change_summary' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $renderer->validateLaunch(data_get($api->siteSchemaDraft(), 'site_version', []));
        $published = $api->publishSiteSchema(
            $validated['checksum'],
            $validated['change_summary'],
            (string) Str::ulid(),
        );
        $renderer->rootPage(data_get($published, 'site_version', []));
        Cache::forget('canonical-site.published');

        return back()->with('status', 'Site version published.');
    }

    public function rollback(Request $request, string $version, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeOwner($request);
        $api->rollbackSiteSchema($version, (string) Str::ulid());

        return back()->with('status', 'A new draft was created from the selected version.');
    }

    public function features(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'forms_enabled' => ['nullable', 'boolean'],
            'analytics_enabled' => ['nullable', 'boolean'],
            'analytics_provider' => ['nullable', 'in:plausible,google-analytics'],
            'analytics_measurement_id' => ['nullable', 'string', 'max:120'],
        ]);
        $api->updateSiteSchema('settings.update', [
            'namespace' => 'features',
            'value' => [
                'forms' => ['enabled' => (bool) ($validated['forms_enabled'] ?? false)],
                'analytics' => [
                    'enabled' => (bool) ($validated['analytics_enabled'] ?? false),
                    'provider' => $validated['analytics_provider'] ?? null,
                    'measurement_id' => $validated['analytics_measurement_id'] ?? null,
                ],
            ],
        ], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Feature settings saved.');
    }

    public function integrations(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'integrations' => ['required', 'json'],
        ]);
        $integrations = json_decode($validated['integrations'], true, flags: JSON_THROW_ON_ERROR);
        abort_unless(is_array($integrations) && array_is_list($integrations), 422);
        $api->updateSiteSchema('integrations.replace', [
            'integrations' => $integrations,
        ], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Integrations saved.');
    }

    private function authorizeEditor(Request $request): void
    {
        abort_unless($request->user()->canEditSite(), 403);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()->canPublishSite(), 403);
    }
}
