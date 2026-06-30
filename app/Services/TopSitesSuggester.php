<?php

namespace App\Services;

use Keystone\Toolkit\Services\OpenRouterService;
use Throwable;

/**
 * Suggests well-known, high-performing websites for a business category and region so the client
 * has inspiration references even when they don't have their own in mind. Backed by OpenRouter.
 */
class TopSitesSuggester
{
    public const TASK = 'top_sites';

    public function __construct(private OpenRouterService $openRouter)
    {
    }

    /**
     * @return array<int, array{name: string, domain: string, reason: string}>
     */
    public function suggest(string $category, string $region, string $scope, int $limit = 5): array
    {
        $scopeLabel = $scope === 'national' ? 'countrywide' : 'regional/local';

        try {
            $result = $this->openRouter->request([
                'task' => self::TASK,
                'response_type' => 'json_object',
                'temperature' => 0.4,
                'directives' => 'You are a competitive web research assistant. You recommend real, well-known, '
                    .'high-performing business websites worth studying for design and conversion. Return valid JSON only.',
                'prompt' => implode("\n", [
                    "Business category: {$category}",
                    "Location: {$region}",
                    "Audience reach: {$scopeLabel}",
                    '',
                    "List the top {$limit} real, currently-operating websites in this category that are known to "
                        .'be top earners or category leaders for this audience reach. Prefer recognizable brands.',
                    'Return JSON with this exact shape:',
                    '{"sites":[{"name":string,"domain":string,"reason":string}]}',
                    '- domain is the bare hostname (e.g. "example.com"), no protocol or path.',
                    '- reason is one short sentence on why it is worth studying.',
                ]),
            ], static fn ($response): bool => is_array($response)
                && isset($response['sites'])
                && is_array($response['sites'])
                && $response['sites'] !== []
            );
        } catch (Throwable) {
            return [];
        }

        return $this->normalize($result['sites'] ?? [], $limit);
    }

    /**
     * @param  array<int, mixed>  $sites
     * @return array<int, array{name: string, domain: string, reason: string}>
     */
    private function normalize(array $sites, int $limit): array
    {
        $clean = [];
        $seen = [];

        foreach ($sites as $site) {
            if (! is_array($site)) {
                continue;
            }

            $domain = $this->bareDomain((string) ($site['domain'] ?? ''));

            if ($domain === '' || isset($seen[$domain])) {
                continue;
            }

            $seen[$domain] = true;
            $clean[] = [
                'name' => trim((string) ($site['name'] ?? $domain)) ?: $domain,
                'domain' => $domain,
                'reason' => trim((string) ($site['reason'] ?? '')),
            ];

            if (count($clean) >= $limit) {
                break;
            }
        }

        return $clean;
    }

    private function bareDomain(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;

        return preg_replace('#^www\.#', '', trim($value)) ?? $value;
    }
}
