<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Keystone\Admin\Models\SectionTemplate;
use Tests\TestCase;

class SiteStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_updates_pages_and_sections_through_canonical_commands(): void
    {
        $this->configureApi();
        $checksum = str_repeat('a', 64);
        Http::fake([
            '*/site-schema/draft' => Http::response($this->draft($checksum)),
            '*/site-schema/commands' => Http::response($this->draft(str_repeat('b', 64))),
        ]);
        $editor = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_EDITOR]);
        SectionTemplate::query()->create([
            'key' => 'hero.default',
            'name' => 'Homepage hero',
            'section_type' => 'hero',
            'source_type' => 'system',
            'blade_view' => 'sections.hero.default',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->actingAs($editor)
            ->get(route('keystone.admin.site-structure.show'))
            ->assertOk()
            ->assertSee('Pages and sections')
            ->assertSee('Homepage hero');

        $this->actingAs($editor)
            ->put(route('keystone.admin.site-structure.page'), [
                'checksum' => $checksum,
                'page_id' => 'page_home',
                'title' => 'Welcome',
                'path' => '/',
                'template' => 'default',
                'status' => 'enabled',
                'order' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Page saved.');

        $this->actingAs($editor)
            ->put(route('keystone.admin.site-structure.section'), [
                'checksum' => $checksum,
                'page_id' => 'page_home',
                'section_id' => 'section_hero',
                'name' => 'Homepage hero',
                'type' => 'hero',
                'template' => 'hero.default',
                'order' => 0,
                'enabled' => '1',
                'content' => json_encode(['heading' => 'Fresh coffee']),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Section saved.');

        $this->actingAs($editor)
            ->put(route('keystone.admin.site-structure.navigation'), [
                'checksum' => $checksum,
                'navigation' => json_encode(['menus' => []]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Navigation saved.');

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://kirbycreative.co/api/site-schema/commands'
            && $request['command'] === 'page.upsert'
            && data_get($request->data(), 'payload.page.title') === 'Welcome');
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://kirbycreative.co/api/site-schema/commands'
            && $request['command'] === 'section.upsert'
            && data_get($request->data(), 'payload.section.content.heading') === 'Fresh coffee');
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://kirbycreative.co/api/site-schema/commands'
            && $request['command'] === 'navigation.replace'
            && data_get($request->data(), 'payload.menus') === []);
    }

    public function test_viewer_cannot_change_site_structure(): void
    {
        $this->configureApi();
        Http::fake(['*/site-schema/draft' => Http::response($this->draft(str_repeat('a', 64)))]);
        $viewer = User::factory()->create(['onboarded' => true, 'role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get(route('keystone.admin.site-structure.show'))
            ->assertOk()
            ->assertSee('View only');

        $this->actingAs($viewer)
            ->put(route('keystone.admin.site-structure.page'), [
                'checksum' => str_repeat('a', 64),
                'title' => 'Forbidden',
                'path' => '/forbidden',
                'template' => 'default',
                'status' => 'enabled',
                'order' => 1,
            ])
            ->assertForbidden();

        Http::assertSentCount(1);
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
                'pages' => [[
                    'id' => 'page_home',
                    'parent_id' => null,
                    'slug' => '',
                    'path' => '/',
                    'title' => 'Home',
                    'navigation_label' => 'Home',
                    'template' => 'default',
                    'status' => 'enabled',
                    'order' => 0,
                    'settings' => [],
                    'seo' => [
                        'title' => 'Home',
                        'description' => null,
                        'canonical_url' => null,
                        'robots' => ['index', 'follow'],
                        'open_graph' => [],
                        'structured_data' => [],
                    ],
                    'sections' => [[
                        'id' => 'section_hero',
                        'type' => 'hero',
                        'template' => 'hero.default',
                        'name' => 'Homepage hero',
                        'order' => 0,
                        'enabled' => true,
                        'settings' => [],
                        'content' => ['heading' => 'Hello'],
                    ]],
                ]],
            ],
        ]];
    }
}
