<?php

namespace Keystone\Toolkit\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Authenticated client for the Kirby Creative API. The client site never holds KC's S3 catalog
 * credentials — it talks to KC through this bearer-token API for template provisioning and to hand
 * off onboarding data (inspiration domains, brand) so KC can start page imports.
 */
class KeystoneApiService
{
    protected string $baseUrl;

    protected ?string $token;

    public function __construct(?string $baseUrl = null, ?string $token = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.keystone.url'), '/');
        $this->token = $token ?? config('services.keystone.token');
    }

    public function configured(): bool
    {
        return $this->baseUrl !== '' && ! empty($this->token);
    }

    /**
     * Hand the client's onboarding selections to KC to kick off page imports.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function startPageImports(array $payload): array
    {
        return $this->post('/onboarding/imports', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Kirby Creative API is not configured (missing KEYSTONE_API_URL or KEYSTONE_API_TOKEN).');
        }

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->timeout((int) config('services.keystone.timeout', 30))
            ->post($this->baseUrl.$path, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Kirby Creative API request failed ('.$response->status().'): '.$response->body());
        }

        return $response->json() ?? [];
    }
}
