<?php

namespace Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Keystone\Toolkit\Services\KeystoneApiService;
use Tests\TestCase;

class KeystoneApiServiceTest extends TestCase
{
    public function test_every_json_endpoint_uses_token_site_header_and_stable_idempotency(): void
    {
        Http::fake(fn () => Http::response([
            'submission' => ['id' => 'sub-1'],
            'site_layout' => ['id' => 'layout-1'],
            'feedback' => ['recorded' => true],
        ], 202));
        $api = new KeystoneApiService('https://kirbycreative.co/api', 'secret', 'https://Client.Example/');

        $api->completeOnboarding(['submission_id' => 'request-1'], 'request-1');
        $api->onboardingCompletion('sub-1');
        $api->decideStyleGuide('sub-1', 'approve');
        $api->decidePageTree('sub-1', 'deny', 'Revise it.');
        $api->asset('asset-1');
        $api->createSiteLayout(['request_id' => 'request-2'], 'request-2');
        $api->siteLayout('layout-1');
        $api->recordAiFeedback('site_layout_page', 'layout-1:home', true);

        Http::assertSentCount(8);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer secret')
            && $request->hasHeader('X-Keystone-Site-Url', 'https://Client.Example'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kirbycreative.co/api/onboarding/completions'
            && $request->hasHeader('Idempotency-Key', 'request-1'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kirbycreative.co/api/site-layouts'
            && $request->hasHeader('Idempotency-Key', 'request-2'));
    }

    public function test_asset_upload_is_multipart_and_uses_the_same_authentication_contract(): void
    {
        Http::fake(['*' => Http::response(['asset' => ['id' => 'asset-1', 'status' => 'queued']], 202)]);
        $file = UploadedFile::fake()->createWithContent('menu.txt', 'Coffee 4.00');

        (new KeystoneApiService('https://kirbycreative.co/api', 'secret', 'https://client.example'))
            ->uploadAsset($file, 42, 'menu', 'Menu', 'Current prices', 'asset-request-1');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://kirbycreative.co/api/assets'
            && $request->hasHeader('Authorization', 'Bearer secret')
            && $request->hasHeader('X-Keystone-Site-Url', 'https://client.example')
            && $request->hasHeader('Idempotency-Key', 'asset-request-1')
            && str_contains($request->body(), 'name="client_asset_id"')
            && str_contains($request->body(), 'name="file"'));
    }
}
