<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_editor_can_update_the_canonical_draft(): void
    {
        $this->configureApi();
        $checksum = str_repeat('a', 64);
        Http::fake([
            '*/site-usage' => Http::response(['usage' => []]),
            '*/site-schema/draft' => Http::response($this->draft($checksum)),
            '*/site-schema/versions' => Http::response(['site_versions' => []]),
            '*/site-schema/commands' => Http::response($this->draft(str_repeat('b', 64))),
        ]);

        foreach ([User::ROLE_OWNER, User::ROLE_EDITOR] as $role) {
            $user = User::factory()->create(['onboarded' => true, 'role' => $role]);

            $this->actingAs($user)
                ->get(route('keystone.admin.site-settings.show'))
                ->assertOk()
                ->assertSee('Site settings')
                ->assertSee('Northstar Coffee');

            $this->actingAs($user)
                ->patch(route('keystone.admin.site-settings.identity'), [
                    'checksum' => $checksum,
                    'name' => 'Northstar Roasters',
                    'legal_name' => 'Northstar Coffee LLC',
                    'tagline' => 'Better mornings.',
                    'locale' => 'en-US',
                    'timezone' => 'America/New_York',
                ])
                ->assertRedirect()
                ->assertSessionHas('status', 'Site identity saved.');
        }

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://kirbycreative.co/api/site-schema/commands'
            && $request['command'] === 'identity.update'
            && data_get($request->data(), 'payload.name') === 'Northstar Roasters'
            && $request->hasHeader('If-Match', $checksum)
            && $request->hasHeader('Idempotency-Key'));

        $this->actingAs(User::where('role', User::ROLE_OWNER)->firstOrFail())
            ->patch(route('keystone.admin.site-settings.features'), [
                'checksum' => $checksum,
                'forms_enabled' => '1',
                'analytics_enabled' => '1',
                'analytics_provider' => 'plausible',
                'analytics_measurement_id' => 'northstar',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Feature settings saved.');

        $this->actingAs(User::where('role', User::ROLE_OWNER)->firstOrFail())
            ->patch(route('keystone.admin.site-settings.integrations'), [
                'checksum' => $checksum,
                'integrations' => json_encode([[
                    'key' => 'contact_delivery',
                    'provider' => 'kirby_mail',
                    'enabled' => true,
                    'settings' => ['recipient' => 'owner@example.com'],
                ]]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Integrations saved.');

        Http::assertSent(fn (ClientRequest $request): bool => data_get($request->data(), 'command') === 'settings.update'
            && data_get($request->data(), 'payload.value.analytics.provider') === 'plausible');
        Http::assertSent(fn (ClientRequest $request): bool => data_get($request->data(), 'command') === 'integrations.replace'
            && data_get($request->data(), 'payload.integrations.0.key') === 'contact_delivery');
    }

    public function test_viewer_can_read_settings_but_cannot_change_them(): void
    {
        $this->configureApi();
        Http::fake([
            '*/site-usage' => Http::response(['usage' => []]),
            '*/site-schema/draft' => Http::response($this->draft(str_repeat('a', 64))),
            '*/site-schema/versions' => Http::response(['site_versions' => []]),
        ]);
        $viewer = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get(route('keystone.admin.site-settings.show'))
            ->assertOk()
            ->assertSee('View only')
            ->assertDontSee('Save identity');

        $this->actingAs($viewer)
            ->patch(route('keystone.admin.site-settings.identity'), [
                'checksum' => str_repeat('a', 64),
                'name' => 'Forbidden',
                'locale' => 'en-US',
                'timezone' => 'America/New_York',
            ])
            ->assertForbidden();

        Http::assertSentCount(3);
    }

    public function test_viewer_cannot_mutate_media_or_page_suggestions(): void
    {
        $viewer = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_VIEWER]);
        $viewer->onboardingState()->update(['generation_stage' => 'content_ready']);

        $this->actingAs($viewer)
            ->post(route('keystone.admin.content.store'))
            ->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('keystone.admin.page-suggestions.generate'))
            ->assertForbidden();
    }

    public function test_only_owner_can_publish_and_create_a_rollback_draft(): void
    {
        $this->configureApi();
        $checksum = str_repeat('a', 64);
        Http::fake([
            '*/site-schema/draft' => Http::response($this->draft($checksum)),
            '*/site-schema/publish' => Http::response($this->draft(str_repeat('b', 64)), 201),
            '*/site-schema/versions/*/rollback' => Http::response($this->draft(str_repeat('b', 64))),
        ]);
        $owner = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_OWNER]);
        $editor = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_EDITOR]);
        Cache::put('canonical-site.published', ['stale' => true], 300);

        $this->actingAs($owner)
            ->post(route('keystone.admin.site-settings.publish'), [
                'checksum' => $checksum,
                'change_summary' => 'Initial reviewed publication.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Site version published.');
        $this->assertFalse(Cache::has('canonical-site.published'));

        $this->actingAs($owner)
            ->post(route('keystone.admin.site-settings.rollback', '01VERSION'))
            ->assertRedirect()
            ->assertSessionHas('status', 'A new draft was created from the selected version.');

        $this->actingAs($editor)
            ->post(route('keystone.admin.site-settings.publish'), [
                'checksum' => $checksum,
                'change_summary' => 'Editors cannot publish.',
            ])
            ->assertForbidden();

        Http::assertSentCount(3);
    }

    private function configureApi(): void
    {
        config([
            'app.url' => 'https://northstar.example',
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'site-token',
        ]);
    }

    private function draft(string $checksum): array
    {
        return ['site_version' => [
            'checksum' => $checksum,
            'document' => [
                'identity' => [
                    'name' => 'Northstar Coffee',
                    'legal_name' => null,
                    'tagline' => 'Better mornings.',
                    'locale' => 'en-US',
                    'timezone' => 'America/New_York',
                ],
                'seo' => [
                    'title_template' => '%s | Northstar Coffee',
                    'default_description' => 'Neighborhood coffee.',
                    'sitemap_enabled' => true,
                ],
                'domains' => [
                    'primary' => 'northstar.example',
                    'redirects' => [],
                ],
                'pages' => [[
                    'id' => 'page_home',
                    'path' => '/',
                    'title' => 'Home',
                    'status' => 'enabled',
                    'seo' => ['title' => 'Home'],
                    'sections' => [],
                ]],
            ],
        ]];
    }
}
