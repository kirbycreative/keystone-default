<?php

namespace Keystone\Toolkit\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Authenticated client for the Kirby Creative API. The client site never holds KC's S3 catalog
 * credentials or AI-provider credentials. It talks to Kirby Creative through this bearer-token API
 * for centrally owned generation and proprietary services.
 */
class KeystoneApiService
{
    protected string $baseUrl;

    protected ?string $token;

    protected string $siteUrl;

    public function __construct(?string $baseUrl = null, ?string $token = null, ?string $siteUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.keystone.url'), '/');
        $this->token = $token ?? config('services.keystone.token');
        $this->siteUrl = rtrim($siteUrl ?? (string) config('app.url'), '/');
    }

    public function configured(): bool
    {
        return $this->baseUrl !== '' && ! empty($this->token) && $this->validSiteUrl();
    }

    /**
     * Submit the completed onboarding brief exactly once.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function completeOnboarding(array $payload, string $submissionId): array
    {
        return $this->post('/onboarding/completions', $payload, [
            'Idempotency-Key' => $submissionId,
        ]);
    }

    /** @return array<string, mixed> */
    public function onboardingCompletion(string $id): array
    {
        return $this->get('/onboarding/completions/'.rawurlencode($id));
    }

    public function decideStyleGuide(string $id, string $decision, ?string $feedback = null): array
    {
        return $this->post('/onboarding/completions/'.rawurlencode($id).'/style-guide-decision', compact('decision', 'feedback'));
    }

    public function decidePageTree(string $id, string $decision, ?string $feedback = null): array
    {
        return $this->post('/onboarding/completions/'.rawurlencode($id).'/page-tree-decision', compact('decision', 'feedback'));
    }

    public function uploadAsset(UploadedFile $file, int $clientAssetId, string $type, ?string $title, ?string $notes, string $requestId): array
    {
        $fields = array_filter([
            'client_asset_id' => $clientAssetId,
            'type' => $type,
            'title' => $title,
            'notes' => $notes,
        ], static fn ($value): bool => $value !== null);

        return $this->multipart('/assets', $fields, $file, ['Idempotency-Key' => $requestId]);
    }

    public function asset(string $id): array
    {
        return $this->get('/assets/'.rawurlencode($id));
    }

    /** @param array<string, mixed> $payload */
    public function createSiteLayout(array $payload, string $requestId): array
    {
        return $this->post('/site-layouts', $payload, ['Idempotency-Key' => $requestId]);
    }

    public function siteLayout(string $id): array
    {
        return $this->get('/site-layouts/'.rawurlencode($id));
    }

    public function recordAiFeedback(string $resourceType, string $resourceId, bool $helpful, ?string $reason = null): array
    {
        return $this->post('/ai-feedback', [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'helpful' => $helpful,
            'reason' => $reason,
        ]);
    }

    /**
     * Run an AI action owned and configured by the Kirby Creative API.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function runAiAction(string $action, array $payload): array
    {
        if (! preg_match('/^[A-Za-z0-9._-]+$/', $action)) {
            throw new RuntimeException('Invalid Kirby Creative AI action path.');
        }

        $response = $this->post('/ai/'.$action, $payload);
        $result = $response['result'] ?? null;

        if (! is_array($result)) {
            throw new RuntimeException('Kirby Creative AI action returned an invalid result.');
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload, array $headers = []): array
    {
        return $this->request('POST', $path, $payload, $headers);
    }

    /** @return array<string, mixed> */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = [], array $headers = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Kirby Creative API is not configured (requires KEYSTONE_API_URL, KEYSTONE_API_TOKEN, and a valid APP_URL).');
        }

        $response = $this->client($headers)
            ->timeout((int) config('services.keystone.timeout', 30))
            ->send($method, $this->baseUrl.$path, $payload === [] ? [] : ['json' => $payload]);

        if ($response->failed()) {
            throw new RuntimeException('Kirby Creative API request failed ('.$response->status().'): '.$response->body());
        }

        return $response->json() ?? [];
    }

    /** @param array<string, scalar> $fields */
    private function multipart(string $path, array $fields, UploadedFile $file, array $headers = []): array
    {
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The selected asset could not be read.');
        }

        $response = $this->client($headers)
            ->timeout((int) config('services.keystone.timeout', 30))
            ->attach('file', $stream, $file->getClientOriginalName())
            ->post($this->baseUrl.$path, $fields);

        if ($response->failed()) {
            throw new RuntimeException('Kirby Creative API request failed ('.$response->status().'): '.$response->body());
        }

        return $response->json() ?? [];
    }

    /** @param array<string, string> $headers */
    private function client(array $headers = []): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('Kirby Creative API is not configured (requires KEYSTONE_API_URL, KEYSTONE_API_TOKEN, and a valid APP_URL).');
        }

        return Http::withToken($this->token)
            ->acceptJson()
            ->withHeaders(array_merge($headers, ['X-Keystone-Site-Url' => $this->siteUrl]));
    }

    private function validSiteUrl(): bool
    {
        if (filter_var($this->siteUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($this->siteUrl);

        return is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && filled($parts['host'] ?? null)
            && in_array($parts['path'] ?? '', ['', '/'], true)
            && ! isset($parts['query'])
            && ! isset($parts['fragment']);
    }
}
