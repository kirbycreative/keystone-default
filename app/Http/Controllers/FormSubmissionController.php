<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Services\CanonicalSiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Keystone\Toolkit\Services\KeystoneApiService;

class FormSubmissionController extends Controller
{
    public function store(
        Request $request,
        string $section,
        KeystoneApiService $api,
        CanonicalSiteRenderer $renderer,
    ): RedirectResponse {
        abort_if($request->filled('website'), 422);
        $version = Cache::remember(
            'canonical-site.published',
            now()->addMinutes(5),
            fn (): array => data_get($api->siteSchemaPublished(), 'site_version', []),
        );
        abort_unless(data_get($version, 'document.settings.features.forms.enabled', false), 404);
        $path = '/'.ltrim((string) $request->input('page_path', '/'), '/');
        $page = $renderer->page($version, $path);
        $form = collect($page['sections'] ?? [])->first(
            fn ($candidate): bool => is_array($candidate)
                && ($candidate['id'] ?? null) === $section
                && ($candidate['enabled'] ?? false)
                && in_array($candidate['type'] ?? null, ['form', 'contact'], true),
        );
        abort_unless($form, 404);

        $payload = $request->validate([
            'name' => ['nullable', 'string', 'max:180'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'message' => ['required', 'string', 'max:10000'],
        ]);
        FormSubmission::create([
            'page_path' => $path,
            'section_id' => $section,
            'payload' => $payload,
        ]);

        return back()->with('form_status', 'Thanks. Your message was received.');
    }
}
