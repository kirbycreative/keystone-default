<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Keystone\Toolkit\Services\KeystoneApiService;

class SiteStructureController extends AdminController
{
    public function show(KeystoneApiService $api): View
    {
        page()->setTitle('Site structure');

        return view('admin.site-structure', [
            'siteVersion' => data_get($api->siteSchemaDraft(), 'site_version'),
        ]);
    }

    public function page(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'page_id' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:180'],
            'path' => ['required', 'string', 'max:2048', 'regex:#^/(?:[a-z0-9][a-z0-9-]*(?:/[a-z0-9][a-z0-9-]*)*)?$#'],
            'template' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'status' => ['required', 'in:enabled,disabled'],
            'order' => ['required', 'integer', 'min:0'],
        ]);
        $document = $this->document($api);
        $page = collect($document['pages'] ?? [])->firstWhere('id', $validated['page_id']) ?? [
            'id' => 'page_'.strtolower((string) Str::ulid()),
            'parent_id' => null,
            'navigation_label' => null,
            'settings' => [],
            'seo' => [
                'title' => null,
                'description' => null,
                'canonical_url' => null,
                'robots' => ['index', 'follow'],
                'open_graph' => [],
                'structured_data' => [],
            ],
            'sections' => [],
        ];
        $page = array_replace($page, [
            'slug' => $validated['path'] === '/' ? '' : basename($validated['path']),
            'path' => $validated['path'],
            'title' => $validated['title'],
            'navigation_label' => $validated['title'],
            'template' => $validated['template'],
            'status' => $validated['status'],
            'order' => $validated['order'],
        ]);

        $api->updateSiteSchema('page.upsert', ['page' => $page], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Page saved.');
    }

    public function deletePage(Request $request, string $page, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate(['checksum' => ['required', 'string', 'size:64']]);
        $api->updateSiteSchema('page.delete', ['page_id' => $page], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Page removed from the draft.');
    }

    public function section(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'page_id' => ['required', 'string', 'max:80'],
            'section_id' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'template' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'order' => ['required', 'integer', 'min:0'],
            'enabled' => ['nullable', 'boolean'],
            'content' => ['required', 'json'],
        ]);
        $document = $this->document($api);
        $page = collect($document['pages'] ?? [])->firstWhere('id', $validated['page_id']);
        abort_unless($page, 404);
        $section = collect($page['sections'])->firstWhere('id', $validated['section_id']) ?? [
            'id' => 'section_'.strtolower((string) Str::ulid()),
            'settings' => [],
        ];
        $section = array_replace($section, [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'template' => $validated['template'],
            'order' => $validated['order'],
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'content' => json_decode($validated['content'], true, flags: JSON_THROW_ON_ERROR),
        ]);
        $api->updateSiteSchema('section.upsert', [
            'page_id' => $validated['page_id'],
            'section' => $section,
        ], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Section saved.');
    }

    public function deleteSection(
        Request $request,
        string $page,
        string $section,
        KeystoneApiService $api,
    ): RedirectResponse {
        $this->authorizeEditor($request);
        $validated = $request->validate(['checksum' => ['required', 'string', 'size:64']]);
        $api->updateSiteSchema('section.delete', [
            'page_id' => $page,
            'section_id' => $section,
        ], $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Section removed from the draft.');
    }

    public function navigation(Request $request, KeystoneApiService $api): RedirectResponse
    {
        $this->authorizeEditor($request);
        $validated = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'navigation' => ['required', 'json'],
        ]);
        $navigation = json_decode($validated['navigation'], true, flags: JSON_THROW_ON_ERROR);
        abort_unless(is_array($navigation), 422);
        $api->updateSiteSchema('navigation.replace', $navigation, $validated['checksum'], (string) Str::ulid());

        return back()->with('status', 'Navigation saved.');
    }

    private function document(KeystoneApiService $api): array
    {
        return data_get($api->siteSchemaDraft(), 'site_version.document', []);
    }

    private function authorizeEditor(Request $request): void
    {
        abort_unless($request->user()->canEditSite(), 403);
    }
}
