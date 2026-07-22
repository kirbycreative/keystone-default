<?php

namespace Tests\Unit;

use App\Services\TopSitesSuggester;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Keystone\Toolkit\Services\KeystoneApiService;
use Mockery;
use Tests\TestCase;

class TopSitesSuggesterTest extends TestCase
{
    public function test_it_requests_and_normalizes_suggestions_from_the_kirby_creative_api(): void
    {
        $api = Mockery::mock(KeystoneApiService::class);
        $api->shouldReceive('runAiAction')
            ->once()
            ->with('top_sites', [
                'business_category' => 'Specialty coffee shop',
                'primary_location' => 'Austin, TX',
                'audience_reach' => 'regional',
                'limit' => 2,
            ])
            ->andReturn([
                'sites' => [
                    ['name' => 'Example Coffee', 'domain' => 'https://www.example.com/menu', 'reason' => 'Clear product storytelling.'],
                    ['name' => 'Duplicate', 'domain' => 'example.com', 'reason' => 'Duplicate domain.'],
                    ['name' => 'Second Coffee', 'domain' => 'second.example', 'reason' => 'Strong local conversion path.'],
                ],
            ]);

        $sites = (new TopSitesSuggester($api))->suggest(
            'Specialty coffee shop',
            'Austin, TX',
            'regional',
            2,
        );

        $this->assertSame([
            ['name' => 'Example Coffee', 'domain' => 'example.com', 'reason' => 'Clear product storytelling.'],
            ['name' => 'Second Coffee', 'domain' => 'second.example', 'reason' => 'Strong local conversion path.'],
        ], $sites);
    }

    public function test_api_client_uses_the_authenticated_kirby_creative_ai_action_endpoint(): void
    {
        Http::fake([
            'https://kirbycreative.co/api/ai/top_sites' => Http::response([
                'action' => 'top_sites',
                'result' => ['sites' => []],
            ]),
        ]);

        $result = (new KeystoneApiService(
            'https://kirbycreative.co/api',
            'client-token',
            'https://northstar.example',
        ))
            ->runAiAction('top_sites', ['business_category' => 'Coffee']);

        $this->assertSame(['sites' => []], $result);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kirbycreative.co/api/ai/top_sites'
            && $request->hasHeader('Authorization', 'Bearer client-token')
            && $request->hasHeader('X-Keystone-Site-Url', 'https://northstar.example')
            && $request['business_category'] === 'Coffee'
        );
    }
}
