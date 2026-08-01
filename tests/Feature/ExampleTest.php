<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_renders_the_public_site_without_exposing_the_admin_application(): void
    {
        $this->configureApi();
        Http::fake(['*/site-schema/published' => Http::response($this->version('Published homepage'))]);
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('site.page')
            ->assertSee('Published homepage')
            ->assertDontSee(route('keystone.admin.dashboard'))
            ->assertDontSee(route('login'))
            ->assertDontSee('Admin Dashboard');
    }

    public function test_an_unpublished_new_site_sends_the_owner_to_login(): void
    {
        $this->configureApi();
        Http::fake([
            '*/site-schema/published' => Http::response([
                'message' => 'This site has not been published.',
            ], 404),
        ]);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_preview_and_public_site_use_the_same_renderer_with_different_versions(): void
    {
        $this->configureApi();
        Http::fake([
            '*/site-schema/published' => Http::response($this->version('Published homepage')),
            '*/site-schema/draft' => Http::response($this->version('Draft homepage')),
        ]);
        $user = User::factory()->create(['onboarded' => true]);

        $this->get('/')->assertOk()->assertViewIs('site.page')->assertSee('Published homepage');
        $this->actingAs($user)
            ->get(route('admin.site-preview'))
            ->assertOk()
            ->assertViewIs('site.page')
            ->assertSee('Draft preview')
            ->assertSee('Draft homepage');
    }

    public function test_public_pages_metadata_discovery_and_domain_redirects_use_the_published_document(): void
    {
        $this->configureApi();
        Http::fake(['*/site-schema/published' => Http::response($this->version('Published homepage'))]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About us')
            ->assertSee('<title>About | Northstar Coffee</title>', false)
            ->assertSee('<link rel="canonical" href="https://northstar.example/about">', false)
            ->assertSee('plausible.io/js/script.js', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://northstar.example/about', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://northstar.example/sitemap.xml');

        $this->get('http://www.northstar.example/about?source=redirect')
            ->assertRedirect('https://northstar.example/about?source=redirect');
    }

    public function test_enabled_canonical_contact_form_stores_an_encrypted_submission(): void
    {
        $this->configureApi();
        Http::fake(['*/site-schema/published' => Http::response($this->version('Published homepage'))]);

        $this->post(route('forms.store', 'section_contact'), [
            'page_path' => '/contact',
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'message' => 'Please call me.',
        ])->assertRedirect()->assertSessionHas('form_status');

        $submission = FormSubmission::firstOrFail();
        $this->assertSame('ada@example.com', $submission->payload['email']);
        $this->assertStringNotContainsString('ada@example.com', $submission->getRawOriginal('payload'));
    }

    private function configureApi(): void
    {
        config([
            'app.url' => 'https://client.example',
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'site-token',
        ]);
    }

    private function version(string $heading): array
    {
        return ['site_version' => [
            'version' => 1,
            'document' => [
                'identity' => ['locale' => 'en-US'],
                'domains' => [
                    'primary' => 'northstar.example',
                    'redirects' => ['www.northstar.example'],
                ],
                'seo' => [
                    'title_template' => '%s | Northstar Coffee',
                    'default_description' => 'Neighborhood coffee.',
                    'robots' => "User-agent: *\nAllow: /",
                    'sitemap_enabled' => true,
                ],
                'settings' => ['features' => [
                    'forms' => ['enabled' => true],
                    'analytics' => [
                        'enabled' => true,
                        'provider' => 'plausible',
                        'measurement_id' => 'northstar.example',
                    ],
                ]],
                'pages' => [[
                    'id' => 'page_home',
                    'path' => '/',
                    'title' => 'Home',
                    'status' => 'enabled',
                    'seo' => ['title' => 'Home'],
                    'sections' => [[
                        'id' => 'section_hero',
                        'type' => 'hero',
                        'template' => 'generic-content',
                        'enabled' => true,
                        'content' => ['heading' => $heading],
                    ]],
                ], [
                    'id' => 'page_about',
                    'path' => '/about',
                    'title' => 'About',
                    'status' => 'enabled',
                    'seo' => ['title' => 'About'],
                    'sections' => [[
                        'id' => 'section_about',
                        'type' => 'content',
                        'template' => 'generic-content',
                        'enabled' => true,
                        'content' => ['heading' => 'About us'],
                    ]],
                ], [
                    'id' => 'page_contact',
                    'path' => '/contact',
                    'title' => 'Contact',
                    'status' => 'enabled',
                    'seo' => ['title' => 'Contact'],
                    'sections' => [[
                        'id' => 'section_contact',
                        'type' => 'contact',
                        'template' => 'generic-content',
                        'enabled' => true,
                        'content' => ['heading' => 'Contact us'],
                    ]],
                ]],
            ],
        ]];
    }
}
