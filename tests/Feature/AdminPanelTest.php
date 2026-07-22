<?php

namespace Tests\Feature;

use App\Models\ContentAsset;
use App\Models\Onboarding;
use App\Models\PageSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_lands_on_login_before_admin_panel(): void
    {
        $this->get('/')->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<input-text name="email"', false)
            ->assertSee('<input-text name="password"', false)
            ->assertSee('<input-checkbox name="remember"', false)
            ->assertSee('<input-button class="form-button"', false)
            ->assertSee('Reset your password');
    }

    public function test_https_proxy_headers_are_used_for_routes_and_vite_assets(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->withHeaders([
                'X-Forwarded-Host' => 'client.example.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/login');

        $response->assertOk();
        $this->assertStringNotContainsString('http://client.example.test/', $response->getContent());
        $this->assertStringContainsString('https://client.example.test/build/assets/', $response->getContent());
    }

    public function test_user_can_log_in_and_reach_admin_dashboard(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/')->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Admin Dashboard');
    }

    public function test_header_hides_admin_navigation_until_onboarding_is_complete(): void
    {
        $user = User::factory()->create(['onboarded' => false]);

        $this->actingAs($user)
            ->get(route('admin.onboarding'))
            ->assertOk()
            ->assertSee('href="'.route('admin.onboarding').'" class="logo"', false)
            ->assertDontSee('>Dashboard<', false)
            ->assertDontSee('>Content<', false)
            ->assertDontSee('>Review<', false)
            ->assertDontSee('>Page Suggestions<', false)
            ->assertDontSee('>Templates<', false)
            ->assertSee('Log out');
    }

    public function test_onboarding_saves_the_complete_style_guide_brief(): void
    {
        $user = User::factory()->create(['onboarded' => false]);

        $this->actingAs($user)
            ->get(route('admin.onboarding'))
            ->assertOk()
            ->assertSeeInOrder([
                'About the company',
                'name="company_name"',
                'name="business_category"',
                'name="region"',
                'name="company_description"',
                'Branding',
                'name="brand_personality_voice"',
                'name="brand_styles_to_avoid"',
                'name="existing_brand_assets"',
                'name="slogans"',
                'Customer / target audience',
                'name="ideal_customer"',
                'name="region_scope"',
            ], false)
            ->assertSee('name="company_description"', false)
            ->assertSee('name="ideal_customer"', false)
            ->assertSee('name="brand_personality_voice"', false)
            ->assertSee('name="brand_styles_to_avoid"', false)
            ->assertSee('name="existing_brand_assets"', false);

        $this->actingAs($user)
            ->postJson(route('admin.onboarding.brand'), [
                'company_name' => 'Northstar Coffee',
                'business_category' => 'Specialty coffee shop',
                'company_description' => 'Small-batch coffee with neighborhood hospitality.',
                'ideal_customer' => 'Local professionals who care about quality and craft.',
                'brand_personality_voice' => 'Warm, confident, and refined.',
                'brand_styles_to_avoid' => 'Corporate language and generic stock photography.',
                'existing_brand_assets' => 'Use the licensed Sora font and existing packaging photography.',
                'slogans' => 'Better mornings start here.',
                'region' => 'Austin, TX',
                'region_scope' => Onboarding::SCOPE_REGIONAL,
                'primary_color' => '#123456',
                'secondary_color' => '#abcdef',
                'primary_colors' => ['#123456', '#abcdef', '#f4e8d0'],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $onboarding = $user->onboarding()->firstOrFail();

        $this->assertSame('Small-batch coffee with neighborhood hospitality.', $onboarding->company_description);
        $this->assertSame('Local professionals who care about quality and craft.', $onboarding->ideal_customer);
        $this->assertSame('Warm, confident, and refined.', $onboarding->brand_personality_voice);
        $this->assertSame('Corporate language and generic stock photography.', $onboarding->brand_styles_to_avoid);
        $this->assertSame('Use the licensed Sora font and existing packaging photography.', $onboarding->existing_brand_assets);
        $this->assertSame('#123456', $onboarding->primary_color);
        $this->assertSame('#abcdef', $onboarding->secondary_color);
        $this->assertSame(['#123456', '#abcdef', '#f4e8d0'], $onboarding->primary_colors);
    }

    public function test_inspiration_only_saves_draft_progress_without_starting_generation(): void
    {
        Http::fake();
        $user = User::factory()->create(['onboarded' => false]);
        $user->onboardingState()->update([
            'business_category' => 'Specialty coffee shop',
            'region' => 'Austin, TX',
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.onboarding.inspiration'), [
                'inspiration_domains' => ['https://example.com/menu'],
            ])
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        Http::assertNothingSent();
        $this->assertSame(['example.com'], $user->onboardingState()->fresh()->inspiration_domains);
    }

    public function test_final_onboarding_submission_uses_the_authenticated_api_without_sending_a_user_id(): void
    {
        config([
            'app.url' => 'https://northstar.example',
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'site-token',
        ]);

        Http::fake([
            'https://kirbycreative.co/api/onboarding/completions' => Http::response([
                'submission' => [
                    'id' => '01JONBOARDING',
                    'status' => 'queued',
                ],
            ], 202),
        ]);

        $user = User::factory()->create(['onboarded' => false]);
        $user->onboardingState()->update([
            'step' => Onboarding::STEP_LAUNCH,
            'company_name' => 'Northstar Coffee',
            'company_description' => 'Small-batch coffee with neighborhood hospitality.',
            'business_category' => 'Specialty coffee shop',
            'region' => 'Austin, TX',
            'region_scope' => Onboarding::SCOPE_REGIONAL,
            'ideal_customer' => 'Local professionals.',
            'brand_personality_voice' => 'Warm and refined.',
            'brand_styles_to_avoid' => 'Generic corporate styling.',
            'primary_color' => '#123456',
            'secondary_color' => '#abcdef',
            'primary_colors' => ['#123456', '#abcdef'],
            'inspiration_domains' => ['example.com'],
        ]);
        ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Menu',
            'type' => 'menu',
            'disk' => 'local',
            'path' => 'business-assets/menu.pdf',
            'original_filename' => 'menu.pdf',
            'mime_type' => 'application/pdf',
            'size' => 900,
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_id' => 'asset-menu',
            'remote_status' => 'completed',
        ]);

        $this->actingAs($user)
            ->post(route('admin.onboarding.complete'))
            ->assertRedirect(route('admin.dashboard'));

        Http::assertSent(function (ClientRequest $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://kirbycreative.co/api/onboarding/completions'
                && $request->hasHeader('Authorization', 'Bearer site-token')
                && $request->hasHeader('X-Keystone-Site-Url', 'https://northstar.example')
                && $request->hasHeader('Idempotency-Key', $payload['submission_id'])
                && ! array_key_exists('user_id', $payload)
                && ! array_key_exists('site_id', $payload)
                && ! array_key_exists('customer_id', $payload)
                && data_get($payload, 'company.name') === 'Northstar Coffee'
                && data_get($payload, 'brand.colors.primary') === '#123456'
                && data_get($payload, 'audience.reach') === Onboarding::SCOPE_REGIONAL
                && data_get($payload, 'inspiration.selected_domains') === ['example.com']
                && data_get($payload, 'materials.asset_ids') === ['asset-menu'];
        });

        $user->refresh();
        $onboarding = $user->onboardingState()->fresh();

        $this->assertTrue($user->onboarded);
        $this->assertSame('01JONBOARDING', $onboarding->generation_remote_id);
        $this->assertSame('queued', $onboarding->generation_status);
        $this->assertNotNull($onboarding->generation_started_at);
    }

    public function test_final_onboarding_submission_waits_for_a_completed_remote_asset(): void
    {
        Http::fake();
        $user = User::factory()->create(['onboarded' => false]);
        ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Menu',
            'type' => 'menu',
            'disk' => 'local',
            'path' => 'business-assets/menu.pdf',
            'original_filename' => 'menu.pdf',
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_id' => null,
            'remote_status' => 'failed',
        ]);

        $this->actingAs($user)
            ->from(route('admin.onboarding'))
            ->post(route('admin.onboarding.complete'))
            ->assertRedirect(route('admin.onboarding'))
            ->assertSessionHasErrors('onboarding');

        Http::assertNothingSent();
        $this->assertFalse($user->fresh()->onboarded);
    }

    public function test_onboarding_page_refreshes_remote_asset_processing_status(): void
    {
        config([
            'app.url' => 'https://northstar.example',
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'site-token',
        ]);
        Http::fake([
            'https://kirbycreative.co/api/assets/asset-menu' => Http::response([
                'asset' => [
                    'id' => 'asset-menu',
                    'status' => 'completed',
                    'result' => ['text' => 'Menu content'],
                ],
            ]),
        ]);
        $user = User::factory()->create(['onboarded' => false]);
        $asset = ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Menu',
            'type' => 'menu',
            'disk' => 'local',
            'path' => 'business-assets/menu.pdf',
            'original_filename' => 'menu.pdf',
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_id' => 'asset-menu',
            'remote_status' => 'queued',
        ]);

        $this->actingAs($user)
            ->get(route('admin.onboarding'))
            ->assertOk();

        $asset->refresh();
        $this->assertSame('completed', $asset->remote_status);
        $this->assertSame(ContentAsset::STATUS_PROCESSED, $asset->ingestion_status);
        $this->assertSame(['text' => 'Menu content'], $asset->ingestion_result);
    }

    public function test_content_stays_locked_until_the_page_tree_is_approved(): void
    {
        $user = User::factory()->create(['onboarded' => true]);
        $onboarding = $user->onboardingState();
        $onboarding->update([
            'generation_remote_id' => '01JONBOARDING',
            'generation_status' => 'processing',
            'generation_stage' => 'page_tree_review',
        ]);

        $this->actingAs($user)
            ->get(route('admin.content.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('build');

        $onboarding->update(['generation_stage' => 'content_ready']);

        $this->actingAs($user)
            ->get(route('admin.content.index'))
            ->assertOk();
    }

    public function test_admin_can_upload_a_private_content_asset(): void
    {
        Storage::fake('local');
        config(['keystone.client_assets.disk' => 'local']);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('summer-menu.pdf', 128, 'application/pdf');

        $this->actingAs($user)
            ->post(route('admin.content.store'), [
                'title' => 'Summer Menu',
                'type' => 'menu',
                'notes' => 'Use this for menu sections and seasonal copy.',
                'asset' => $file,
            ])
            ->assertRedirect(route('admin.content.index'));

        $asset = ContentAsset::firstOrFail();

        $this->assertSame($user->id, $asset->user_id);
        $this->assertSame('Summer Menu', $asset->title);
        $this->assertSame('menu', $asset->type);
        $this->assertSame(ContentAsset::STATUS_PENDING, $asset->ingestion_status);
        Storage::disk('local')->assertExists($asset->path);

        $this->actingAs($user)
            ->get(route('admin.content.download', $asset))
            ->assertOk();
    }

    public function test_admin_cannot_upload_a_legacy_binary_word_document(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['onboarded' => true]);

        $this->actingAs($user)
            ->from(route('admin.content.index'))
            ->post(route('admin.content.store'), [
                'title' => 'Legacy document',
                'type' => 'document',
                'asset' => UploadedFile::fake()->create('legacy.doc', 10, 'application/msword'),
            ])
            ->assertRedirect(route('admin.content.index'))
            ->assertSessionHasErrors('asset');

        $this->assertDatabaseCount('content_assets', 0);
    }

    public function test_admin_can_review_uploads_and_generate_page_suggestions(): void
    {
        config([
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'client-token',
            'app.url' => 'http://client.kirbycreative.local',
        ]);
        Http::fake([
            '*/api/site-layouts' => Http::response(['site_layout' => ['id' => 'layout-1', 'status' => 'queued']], 202),
            '*/api/site-layouts/layout-1' => Http::response(['site_layout' => [
                'id' => 'layout-1',
                'status' => 'completed',
                'pages' => [
                    ['title' => 'Home', 'slug' => '/', 'goal' => 'Primary entry point.', 'sections' => [['type' => 'hero']]],
                    ['title' => 'Menu', 'slug' => '/menu', 'goal' => 'Present the menu.', 'sections' => [['type' => 'menu']]],
                    ['title' => 'Offers', 'slug' => '/offers', 'goal' => 'Present current offers.', 'sections' => [['type' => 'offers']]],
                ],
            ]]),
        ]);

        $user = User::factory()->create();
        $menu = ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Summer Menu',
            'type' => 'menu',
            'notes' => 'Use this for menu sections.',
            'disk' => 'local',
            'path' => 'business-assets/menu.pdf',
            'original_filename' => 'menu.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1200,
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_id' => 'asset-menu',
            'remote_status' => 'completed',
        ]);
        $promotion = ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Holiday Ad',
            'type' => 'promotion',
            'notes' => 'Use this for seasonal offers.',
            'disk' => 'local',
            'path' => 'business-assets/holiday-ad.pdf',
            'original_filename' => 'holiday-ad.pdf',
            'mime_type' => 'application/pdf',
            'size' => 900,
            'ingestion_status' => ContentAsset::STATUS_PENDING,
            'remote_id' => 'asset-promotion',
            'remote_status' => 'completed',
        ]);

        $user->onboardingState()->update([
            'generation_remote_id' => 'submission-1',
            'generation_stage' => 'content_ready',
        ]);

        $this->actingAs($user)
            ->get(route('admin.content.review'))
            ->assertOk()
            ->assertSee('Choose the source material for page suggestions.');

        $this->actingAs($user)
            ->post(route('admin.page-suggestions.generate'), [
                'reviewed' => '1',
                'asset_ids' => [$menu->id, $promotion->id],
            ])
            ->assertRedirect(route('admin.page-suggestions.index'));

        $this->assertDatabaseHas('onboardings', ['user_id' => $user->id, 'site_layout_remote_id' => 'layout-1']);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://kirbycreative.co/api/site-layouts'
            && $request->hasHeader('Idempotency-Key')
            && $request['asset_ids'] === ['asset-menu', 'asset-promotion']
            && $request['base_submission_id'] === 'submission-1');

        $this->actingAs($user)
            ->get(route('admin.page-suggestions.index'))
            ->assertOk()
            ->assertSee('Suggested site tree')
            ->assertSee('Menu')
            ->assertSee('Offers');

        $this->assertDatabaseHas('page_suggestions', ['user_id' => $user->id, 'title' => 'Home', 'slug' => '/']);
        $this->assertDatabaseHas('page_suggestions', [
            'user_id' => $user->id,
            'title' => 'Menu',
            'slug' => 'menu',
        ]);
        $this->assertDatabaseHas('page_suggestions', [
            'user_id' => $user->id,
            'title' => 'Offers',
            'slug' => 'offers',
        ]);

    }

    public function test_generator_requires_a_reviewed_asset_selection(): void
    {
        $user = User::factory()->create();

        ContentAsset::create([
            'user_id' => $user->id,
            'title' => 'Brand Guide',
            'type' => 'brand',
            'disk' => 'local',
            'path' => 'business-assets/brand.pdf',
            'original_filename' => 'brand.pdf',
            'mime_type' => 'application/pdf',
            'size' => 900,
            'ingestion_status' => ContentAsset::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->from(route('admin.content.review'))
            ->post(route('admin.page-suggestions.generate'), [
                'reviewed' => '1',
            ])
            ->assertRedirect(route('admin.content.review'))
            ->assertSessionHasErrors('asset_ids');
    }

    public function test_admin_can_approve_a_page_suggestion(): void
    {
        $user = User::factory()->create();
        $suggestion = PageSuggestion::create([
            'user_id' => $user->id,
            'title' => 'Menu',
            'slug' => 'menu',
            'summary' => 'Suggested menu page.',
            'rationale' => 'Menu assets were uploaded.',
            'source_asset_ids' => [],
            'suggested_copy' => ['sections' => ['Featured items']],
            'status' => PageSuggestion::STATUS_SUGGESTED,
            'sort_order' => 10,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.page-suggestions.status', $suggestion), [
                'status' => PageSuggestion::STATUS_APPROVED,
            ])
            ->assertRedirect(route('admin.page-suggestions.index'));

        $suggestion->refresh();

        $this->assertSame(PageSuggestion::STATUS_APPROVED, $suggestion->status);
        $this->assertNull($suggestion->rejection_feedback);
        $this->assertNotNull($suggestion->reviewed_at);
    }

    public function test_admin_can_deny_a_page_suggestion_with_feedback(): void
    {
        $user = User::factory()->create();
        $suggestion = PageSuggestion::create([
            'user_id' => $user->id,
            'title' => 'Offers',
            'slug' => 'offers',
            'summary' => 'Suggested offers page.',
            'rationale' => 'Promotion assets were uploaded.',
            'source_asset_ids' => [],
            'suggested_copy' => ['sections' => ['Current offer']],
            'status' => PageSuggestion::STATUS_SUGGESTED,
            'sort_order' => 20,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.page-suggestions.status', $suggestion), [
                'status' => PageSuggestion::STATUS_REJECTED,
                'rejection_feedback' => 'This should focus on catering instead of discounts.',
            ])
            ->assertRedirect(route('admin.page-suggestions.index'));

        $suggestion->refresh();

        $this->assertSame(PageSuggestion::STATUS_REJECTED, $suggestion->status);
        $this->assertSame('This should focus on catering instead of discounts.', $suggestion->rejection_feedback);
        $this->assertNotNull($suggestion->reviewed_at);
    }

    public function test_denied_page_suggestion_requires_feedback(): void
    {
        $user = User::factory()->create();
        $suggestion = PageSuggestion::create([
            'user_id' => $user->id,
            'title' => 'Gallery',
            'slug' => 'gallery',
            'summary' => 'Suggested gallery page.',
            'rationale' => 'Photo assets were uploaded.',
            'source_asset_ids' => [],
            'suggested_copy' => ['sections' => ['Featured images']],
            'status' => PageSuggestion::STATUS_SUGGESTED,
            'sort_order' => 30,
        ]);

        $this->actingAs($user)
            ->from(route('admin.page-suggestions.index'))
            ->patch(route('admin.page-suggestions.status', $suggestion), [
                'status' => PageSuggestion::STATUS_REJECTED,
            ])
            ->assertRedirect(route('admin.page-suggestions.index'))
            ->assertSessionHasErrors('rejection_feedback');
    }
}
