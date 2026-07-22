<?php

namespace App\Services;

use Keystone\Toolkit\Services\KeystoneApiService;

/**
 * Suggests well-known, high-performing websites for a business category and region so the client
 * has inspiration references even when they don't have their own in mind. The AI action and its
 * provider credentials are owned by the Kirby Creative API.
 */
class TopSitesSuggester
{
    public const TASK = 'top_sites';

    public function __construct(private KeystoneApiService $keystoneApi) {}

    /**
     * @return array<int, array{name: string, domain: string, reason: string}>
     */
    public function suggest(string $category, string $region, string $scope, int $limit = 5): array
    {
        $result = $this->keystoneApi->runAiAction(self::TASK, [
            'business_category' => $category,
            'primary_location' => $region,
            'audience_reach' => $scope,
            'limit' => $limit,
        ]);

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
