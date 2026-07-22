<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use App\Models\Onboarding;
use App\Services\ContentAssetSynchronizer;
use App\Services\TopSitesSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Keystone\Toolkit\Forms\Form;
use Keystone\Toolkit\Services\KeystoneApiService;
use Throwable;

class OnboardingController extends AdminController
{
    public function show(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->onboarded) {
            return redirect()->route('admin.dashboard');
        }

        $onboarding = $user->onboardingState();
        app(ContentAssetSynchronizer::class)->syncForUser((int) $user->id);

        page()->setTitle('Getting Started');
        page()->setData('onboarding', [
            'step' => (int) $onboarding->step,
            'dnsVerified' => (bool) $onboarding->dns_verified,
            'inspiration' => $onboarding->inspiration_domains ?? [],
            'suggested' => $onboarding->suggested_sites ?? [],
            'assetCount' => ContentAsset::query()->where('user_id', $user->id)->count(),
            'routes' => [
                'checkDns' => route('admin.onboarding.check-dns'),
                'brand' => route('admin.onboarding.brand'),
                'suggestSites' => route('admin.onboarding.suggest-sites'),
                'inspiration' => route('admin.onboarding.inspiration'),
                'assetUpload' => route('admin.onboarding.assets'),
                'materials' => route('admin.onboarding.materials'),
                'complete' => route('admin.onboarding.complete'),
            ],
        ]);

        return view('admin.onboarding', [
            'onboarding' => $onboarding,
            'brandForm' => $this->brandForm($onboarding),
            'containerIp' => (string) config('services.container.ip'),
            'containerNameservers' => (array) config('services.container.nameservers'),
            'siteHost' => $this->siteHost(),
            'assets' => ContentAsset::query()->where('user_id', $user->id)->latest()->get(),
        ]);
    }

    /**
     * Step 1: poll DNS. Passes when the site's domain A record matches the container IP, or its
     * nameservers match the configured set. Marks the step done and advances on success.
     */
    public function checkDNS(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboardingState();

        $host = $this->siteHost();
        $expectedIp = (string) config('services.container.ip');
        $expectedNs = array_map('strtolower', (array) config('services.container.nameservers'));

        $aRecords = collect(@dns_get_record($host, DNS_A) ?: [])
            ->pluck('ip')->filter()->values()->all();
        $nsRecords = collect(@dns_get_record($host, DNS_NS) ?: [])
            ->pluck('target')->filter()->map(fn ($t) => strtolower((string) $t))->values()->all();

        $ipMatch = $expectedIp !== '' && in_array($expectedIp, $aRecords, true);
        $nsMatch = $expectedNs !== [] && array_intersect($expectedNs, $nsRecords) !== [];
        $verified = $ipMatch || $nsMatch;

        if ($verified && ! $onboarding->dns_verified) {
            $onboarding->update([
                'dns_verified' => true,
                'step' => max((int) $onboarding->step, Onboarding::STEP_BRAND),
            ]);
        }

        return response()->json([
            'verified' => $verified,
            'host' => $host,
            'a_records' => $aRecords,
            'ns_records' => $nsRecords,
        ]);
    }

    /**
     * Step 2: company & brand. Stores details, the logo (public S3), and primary colors.
     */
    public function saveBrand(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboardingState();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_description' => ['nullable', 'string', 'max:4000'],
            'ideal_customer' => ['nullable', 'string', 'max:2000'],
            'brand_personality_voice' => ['nullable', 'string', 'max:2000'],
            'brand_styles_to_avoid' => ['nullable', 'string', 'max:2000'],
            'existing_brand_assets' => ['nullable', 'string', 'max:2000'],
            'slogans' => ['nullable', 'string', 'max:2000'],
            'business_category' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'region_scope' => ['required', 'in:regional,national'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'primary_colors' => ['nullable', 'array', 'max:8'],
            'primary_colors.*' => ['nullable', 'string', 'max:32'],
            'primary_color' => ['nullable', 'string', 'max:32'],
            'secondary_color' => ['nullable', 'string', 'max:32'],
        ]);

        $attributes = [
            'company_name' => $validated['company_name'],
            'company_description' => $validated['company_description'] ?? null,
            'ideal_customer' => $validated['ideal_customer'] ?? null,
            'brand_personality_voice' => $validated['brand_personality_voice'] ?? null,
            'brand_styles_to_avoid' => $validated['brand_styles_to_avoid'] ?? null,
            'existing_brand_assets' => $validated['existing_brand_assets'] ?? null,
            'slogans' => $validated['slogans'] ?? null,
            'business_category' => $validated['business_category'],
            'region' => $validated['region'],
            'region_scope' => $validated['region_scope'],
            'primary_colors' => array_values(array_filter($validated['primary_colors'] ?? [])),
            'primary_color' => $validated['primary_color'] ?? null,
            'secondary_color' => $validated['secondary_color'] ?? null,
            'step' => max((int) $onboarding->step, Onboarding::STEP_INSPIRATION),
        ];

        if ($request->hasFile('logo')) {
            $stored = $request->user()->uploadPublic($request->file('logo'), 'brand');
            $attributes['logo_disk'] = $stored['disk'];
            $attributes['logo_path'] = $stored['path'];
        }

        $onboarding->update($attributes);

        return response()->json([
            'ok' => true,
            'logoUrl' => $onboarding->logoUrl(),
        ]);
    }

    /**
     * Suggest top sites for the saved category/region. Cached on the record; pass ?refresh=1 to regenerate.
     */
    public function suggestSites(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboardingState();

        if (! $onboarding->business_category || ! $onboarding->region) {
            return response()->json(['sites' => []]);
        }

        if (! $request->boolean('refresh') && ! empty($onboarding->suggested_sites)) {
            return response()->json(['sites' => $onboarding->suggested_sites]);
        }

        try {
            $sites = app(TopSitesSuggester::class)->suggest(
                $onboarding->business_category,
                $onboarding->region,
                $onboarding->region_scope ?? Onboarding::SCOPE_REGIONAL,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Kirby Creative could not generate site suggestions right now.',
            ], 502);
        }

        $onboarding->update(['suggested_sites' => $sites]);

        return response()->json(['sites' => $sites]);
    }

    /** Step 3: save up to five inspiration domains. */
    public function saveInspiration(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboardingState();

        $validated = $request->validate([
            'inspiration_domains' => ['nullable', 'array', 'max:5'],
            'inspiration_domains.*' => ['nullable', 'string', 'max:255'],
        ]);

        $domains = collect($validated['inspiration_domains'] ?? [])
            ->map(fn ($d) => $this->bareDomain((string) $d))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        $onboarding->update([
            'inspiration_domains' => $domains,
            'step' => max((int) $onboarding->step, Onboarding::STEP_MATERIALS),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Step 4: require at least one business asset before the final submission is available. */
    public function saveMaterials(Request $request): JsonResponse
    {
        $assetCount = ContentAsset::query()
            ->where('user_id', $request->user()->id)
            ->count();

        if ($assetCount === 0) {
            return response()->json([
                'message' => 'Upload at least one business material before continuing.',
            ], 422);
        }

        $request->user()->onboardingState()->update([
            'step' => Onboarding::STEP_LAUNCH,
        ]);

        return response()->json(['ok' => true, 'assetCount' => $assetCount]);
    }

    /** Step 5: submit the complete brief and finish onboarding. */
    public function complete(Request $request): RedirectResponse
    {
        $onboarding = $request->user()->onboardingState();

        $remoteAssetIds = ContentAsset::query()
            ->where('user_id', $request->user()->id)
            ->where('remote_status', 'completed')
            ->whereNotNull('remote_id')
            ->pluck('remote_id')
            ->values()
            ->all();

        if ($remoteAssetIds === []) {
            return back()->withErrors([
                'onboarding' => 'Wait for at least one uploaded business material to finish processing before submitting your brief.',
            ]);
        }

        $submissionId = $onboarding->generation_submission_id ?: (string) Str::uuid();

        $onboarding->update([
            'generation_submission_id' => $submissionId,
            'generation_status' => 'submitting',
            'generation_error' => null,
        ]);

        try {
            $response = app(KeystoneApiService::class)->completeOnboarding(
                $this->completionPayload($onboarding, $submissionId, $remoteAssetIds),
                $submissionId,
            );

            $remoteId = data_get($response, 'submission.id');

            if (! is_string($remoteId) || trim($remoteId) === '') {
                throw new \RuntimeException('Kirby Creative returned an invalid onboarding submission.');
            }
        } catch (Throwable $exception) {
            report($exception);
            $onboarding->update([
                'generation_status' => 'failed',
                'generation_error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'onboarding' => 'We could not submit your completed brief. Please try again.',
            ]);
        }

        $onboarding->update([
            'generation_remote_id' => $remoteId,
            'generation_status' => data_get($response, 'submission.status', 'queued'),
            'generation_error' => null,
            'generation_started_at' => now(),
        ]);

        $request->user()->update(['onboarded' => true]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'The hard part is done. Your concepts are now being prepared for review.');
    }

    public function styleGuideDecision(Request $request): RedirectResponse
    {
        return $this->submitDecision($request, 'style_guide');
    }

    public function pageTreeDecision(Request $request): RedirectResponse
    {
        return $this->submitDecision($request, 'page_tree');
    }

    private function submitDecision(Request $request, string $gate): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,deny'],
            'feedback' => [$request->input('decision') === 'deny' ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);
        $onboarding = $request->user()->onboardingState();

        abort_unless($onboarding->generation_remote_id, 409);

        $api = app(KeystoneApiService::class);
        $response = $gate === 'style_guide'
            ? $api->decideStyleGuide($onboarding->generation_remote_id, $validated['decision'], $validated['feedback'] ?? null)
            : $api->decidePageTree($onboarding->generation_remote_id, $validated['decision'], $validated['feedback'] ?? null);
        $submission = data_get($response, 'submission', []);

        $onboarding->update([
            'generation_status' => $submission['status'] ?? $onboarding->generation_status,
            'generation_stage' => $submission['stage'] ?? $onboarding->generation_stage,
            'generation_error' => null,
        ]);

        return back()->with('status', $validated['decision'] === 'approve' ? 'Concept approved.' : 'Revision requested.');
    }

    /** @return array<string, mixed> */
    private function completionPayload(Onboarding $onboarding, string $submissionId, array $remoteAssetIds): array
    {
        return [
            'schema_version' => 1,
            'submission_id' => $submissionId,
            'company' => [
                'name' => $onboarding->company_name,
                'description' => $onboarding->company_description,
                'business_category' => $onboarding->business_category,
                'primary_location' => $onboarding->region,
            ],
            'brand' => [
                'personality_voice' => $onboarding->brand_personality_voice,
                'styles_to_avoid' => $onboarding->brand_styles_to_avoid,
                'existing_assets' => $onboarding->existing_brand_assets,
                'slogans' => $onboarding->slogans,
                'logo_url' => $onboarding->logoUrl(),
                'colors' => [
                    'primary' => $onboarding->primary_color,
                    'secondary' => $onboarding->secondary_color,
                    'palette' => $onboarding->primary_colors ?? [],
                ],
            ],
            'audience' => [
                'ideal_customer' => $onboarding->ideal_customer,
                'reach' => $onboarding->region_scope,
            ],
            'inspiration' => [
                'selected_domains' => $onboarding->inspiration_domains ?? [],
                'suggested_sites_shown' => $onboarding->suggested_sites ?? [],
            ],
            'materials' => [
                'asset_ids' => $remoteAssetIds,
            ],
        ];
    }

    private function brandForm(Onboarding $onboarding): Form
    {
        $schema = Onboarding::getSchema();
        $schema['form']['logo'] = array_merge($schema['form']['logo'], [
            'logoUrl' => $onboarding->logoUrl(),
            'primaryColor' => $onboarding->primary_color,
            'secondaryColor' => $onboarding->secondary_color,
            'colors' => $onboarding->primary_colors ?? [],
        ]);

        return (new Form)
            ->setModel(Onboarding::class)
            ->setSchema($schema)
            ->setData($onboarding)
            ->setAction(route('admin.onboarding.brand'))
            ->setAttributes(['id' => 'brand-form', 'class' => 'flex:column gap:1'])
            ->setSubmit('Save & continue', ['class' => 'btn btn--primary']);
    }

    private function siteHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host ?: request()->getHost();
    }

    private function bareDomain(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;

        return preg_replace('#^www\.#', '', trim($value)) ?? $value;
    }
}
