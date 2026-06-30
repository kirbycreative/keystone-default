<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Onboarding;
use App\Services\TopSitesSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        page()->setTitle('Getting Started');
        page()->setData('onboarding', [
            'step' => (int) $onboarding->step,
            'dnsVerified' => (bool) $onboarding->dns_verified,
            'inspiration' => $onboarding->inspiration_domains ?? [],
            'suggested' => $onboarding->suggested_sites ?? [],
            'routes' => [
                'checkDns' => route('admin.onboarding.check-dns'),
                'brand' => route('admin.onboarding.brand'),
                'suggestSites' => route('admin.onboarding.suggest-sites'),
                'inspiration' => route('admin.onboarding.inspiration'),
                'complete' => route('admin.onboarding.complete'),
            ],
        ]);

        return view('admin.onboarding', [
            'onboarding' => $onboarding,
            'brandForm' => $this->brandForm($onboarding),
            'containerIp' => (string) config('services.container.ip'),
            'containerNameservers' => (array) config('services.container.nameservers'),
            'siteHost' => $this->siteHost(),
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
            'slogans' => ['nullable', 'string', 'max:2000'],
            'business_category' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'region_scope' => ['required', 'in:regional,national'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'primary_colors' => ['nullable', 'array', 'max:8'],
            'primary_colors.*' => ['nullable', 'string', 'max:32'],
        ]);

        $attributes = [
            'company_name' => $validated['company_name'],
            'slogans' => $validated['slogans'] ?? null,
            'business_category' => $validated['business_category'],
            'region' => $validated['region'],
            'region_scope' => $validated['region_scope'],
            'primary_colors' => array_values(array_filter($validated['primary_colors'] ?? [])),
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

        $sites = app(TopSitesSuggester::class)->suggest(
            $onboarding->business_category,
            $onboarding->region,
            $onboarding->region_scope ?? Onboarding::SCOPE_REGIONAL,
        );

        $onboarding->update(['suggested_sites' => $sites]);

        return response()->json(['sites' => $sites]);
    }

    /**
     * Step 3: save up to five inspiration domains and hand the brief to Kirby Creative to begin imports.
     */
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
            'step' => max((int) $onboarding->step, Onboarding::STEP_LAUNCH),
        ]);

        $handoff = $this->startImports($onboarding, $request->user()->id);

        if ($handoff === true) {
            $onboarding->update(['imports_started_at' => now()]);
        }

        return response()->json([
            'ok' => true,
            'importsStarted' => $handoff === true,
            'handoffError' => is_string($handoff) ? $handoff : null,
        ]);
    }

    /**
     * Step 4: finish onboarding and send the client to the asset upload page.
     */
    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarded' => true]);

        return redirect()
            ->route('admin.content.index')
            ->with('status', "You're all set. Drop your business assets here and we'll get to work.");
    }

    /**
     * @return true|string  True on success, or an error string when the handoff could not complete.
     */
    private function startImports(Onboarding $onboarding, int $userId): bool|string
    {
        try {
            app(KeystoneApiService::class)->startPageImports([
                'user_id' => $userId,
                'company_name' => $onboarding->company_name,
                'slogans' => $onboarding->slogans,
                'business_category' => $onboarding->business_category,
                'region' => $onboarding->region,
                'region_scope' => $onboarding->region_scope,
                'primary_colors' => $onboarding->primary_colors ?? [],
                'logo_url' => $onboarding->logoUrl(),
                'inspiration_domains' => $onboarding->inspiration_domains ?? [],
                'suggested_sites' => $onboarding->suggested_sites ?? [],
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return $exception->getMessage();
        }
    }

    private function brandForm(Onboarding $onboarding): Form
    {
        return (new Form())
            ->setModel(Onboarding::class)
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
