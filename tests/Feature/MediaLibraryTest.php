<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_upload_and_list_only_public_images(): void
    {
        Storage::fake('s3');
        config(['keystone.client_assets.disk' => 's3']);
        $editor = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_EDITOR]);

        Storage::disk('s3')->put('99/private/secret.png', 'private');
        Storage::disk('s3')->put('99/public/media/existing.jpg', 'existing');

        $uploaded = $this->actingAs($editor)->postJson(route('admin.media-library.store'), [
            'file' => UploadedFile::fake()->create('hero.png', 128, 'image/png'),
        ])->assertCreated()->json('asset');

        $this->assertStringStartsWith($editor->id.'/public/media/', $uploaded['path']);
        Storage::disk('s3')->assertExists($uploaded['path']);

        $response = $this->actingAs($editor)
            ->getJson(route('admin.media-library.index'))
            ->assertOk();

        $paths = collect($response->json('assets'))->pluck('path');
        $this->assertTrue($paths->contains('99/public/media/existing.jpg'));
        $this->assertTrue($paths->contains($uploaded['path']));
        $this->assertFalse($paths->contains('99/private/secret.png'));
    }

    public function test_viewer_can_browse_but_cannot_upload_media(): void
    {
        Storage::fake('s3');
        config(['keystone.client_assets.disk' => 's3']);
        $viewer = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)->getJson(route('admin.media-library.index'))->assertOk();
        $this->actingAs($viewer)->postJson(route('admin.media-library.store'), [
            'file' => UploadedFile::fake()->create('blocked.png', 128, 'image/png'),
        ])->assertForbidden();
    }
}
