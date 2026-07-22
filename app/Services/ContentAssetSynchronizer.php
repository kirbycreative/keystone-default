<?php

namespace App\Services;

use App\Models\ContentAsset;
use Keystone\Toolkit\Services\KeystoneApiService;
use Throwable;

class ContentAssetSynchronizer
{
    public function __construct(private readonly KeystoneApiService $api) {}

    public function syncForUser(int $userId): void
    {
        ContentAsset::query()
            ->where('user_id', $userId)
            ->whereNotNull('remote_id')
            ->whereNotIn('remote_status', ['completed', 'failed'])
            ->each(function (ContentAsset $asset): void {
                try {
                    $remote = data_get($this->api->asset($asset->remote_id), 'asset', []);
                    $status = $remote['status'] ?? $asset->remote_status;

                    $asset->update([
                        'remote_status' => $status,
                        'remote_error' => data_get($remote, 'error.message'),
                        'ingestion_status' => $status === 'completed' ? ContentAsset::STATUS_PROCESSED : $status,
                        'ingestion_result' => $remote['result'] ?? $asset->ingestion_result,
                    ]);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }
}
