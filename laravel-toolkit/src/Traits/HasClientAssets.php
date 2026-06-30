<?php

namespace Keystone\Toolkit\Traits;

use Illuminate\Http\UploadedFile;
use Keystone\Toolkit\Support\ClientAssetStorage;

/**
 * Convenience asset storage for a user-owned model. All logic lives in {@see ClientAssetStorage};
 * these methods just bind it to the model's id so callers can do $user->uploadPrivate($file) or
 * $user->toPublic($path).
 */
trait HasClientAssets
{
    /**
     * Store an uploaded file under {id}/private/{directory}.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public function uploadPrivate(UploadedFile $file, string $directory = 'uploads'): array
    {
        return ClientAssetStorage::putFile($file, (int) $this->getKey(), ClientAssetStorage::PRIVATE, $directory);
    }

    /**
     * Store an uploaded file under {id}/public/{directory}.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public function uploadPublic(UploadedFile $file, string $directory = 'uploads'): array
    {
        return ClientAssetStorage::putFile($file, (int) $this->getKey(), ClientAssetStorage::PUBLIC, $directory);
    }

    /**
     * Move one of this user's objects into the private tier.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public function toPrivate(string $path): array
    {
        return ClientAssetStorage::move((int) $this->getKey(), $path, ClientAssetStorage::PRIVATE);
    }

    /**
     * Move one of this user's objects into the public tier.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public function toPublic(string $path): array
    {
        return ClientAssetStorage::move((int) $this->getKey(), $path, ClientAssetStorage::PUBLIC);
    }

    /**
     * Storage path prefix for this user's visibility tier, e.g. "42/private".
     */
    public function assetPrefix(string $visibility = ClientAssetStorage::PRIVATE): string
    {
        return ClientAssetStorage::prefix((int) $this->getKey(), $visibility);
    }
}
