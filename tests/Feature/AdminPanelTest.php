<?php

namespace Tests\Feature;

use App\Models\ContentAsset;
use App\Models\PageSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_lands_on_login_before_admin_panel(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
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

    public function test_admin_can_upload_a_private_content_asset(): void
    {
        Storage::fake('local');

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

    public function test_admin_can_review_uploads_and_generate_page_suggestions(): void
    {
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

        $this->assertDatabaseHas('page_suggestions', [
            'user_id' => $user->id,
            'title' => 'Home',
            'slug' => '/',
        ]);
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

        $home = PageSuggestion::where('user_id', $user->id)->where('slug', '/')->firstOrFail();

        $this->assertSame(
            $home->id,
            PageSuggestion::where('user_id', $user->id)->where('slug', 'menu')->firstOrFail()->parent_id
        );

        $this->actingAs($user)
            ->get(route('admin.page-suggestions.index'))
            ->assertOk()
            ->assertSee('Suggested site tree')
            ->assertSee('Menu')
            ->assertSee('Offers');
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
