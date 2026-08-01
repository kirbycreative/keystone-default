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

    public function test_editor_can_upload_and_list_package_owned_cdn_images(): void
    {
        Storage::fake('s3');
        config([
            'keystone.media.disk' => 's3',
            'keystone.site_key' => 'client-99',
        ]);
        $editor = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_EDITOR]);

        $uploaded = $this->actingAs($editor)->postJson(route('keystone.admin.media.store'), [
            'image' => $this->png('hero.png'),
        ])->assertCreated()->json('asset');

        $this->assertStringStartsWith('client-sites/client-99/media/', $uploaded['path']);
        Storage::disk('s3')->assertExists($uploaded['path']);

        $response = $this->actingAs($editor)
            ->getJson(route('keystone.admin.media.index'))
            ->assertOk();

        $paths = collect($response->json('assets'))->pluck('path');
        $this->assertTrue($paths->contains($uploaded['path']));
    }

    public function test_viewer_can_browse_but_cannot_upload_media(): void
    {
        Storage::fake('s3');
        config([
            'keystone.media.disk' => 's3',
            'keystone.site_key' => 'client-99',
        ]);
        $viewer = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)->getJson(route('keystone.admin.media.index'))->assertOk();
        $this->actingAs($viewer)->postJson(route('keystone.admin.media.store'), [
            'image' => $this->png('blocked.png'),
        ])->assertForbidden();
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
    }
}
