<?php

namespace Keystone\Toolkit\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Client-site object storage layout on S3 (or any configured disk).
 *
 * Every client bucket is partitioned by user id, then visibility:
 *   {userId}/public/...   — web-reachable assets (logos, images served by URL)
 *   {userId}/private/...  — ingestion inputs and other non-public material
 */
class ClientAssetStorage
{
    public const PUBLIC = 'public';

    public const PRIVATE = 'private';

    public static function disk(): string
    {
        return (string) config('keystone.client_assets.disk', 's3');
    }

    /**
     * Root prefix for a user's visibility tier, e.g. "42/private".
     */
    public static function prefix(int $userId, string $visibility = self::PRIVATE): string
    {
        $visibility = $visibility === self::PUBLIC ? self::PUBLIC : self::PRIVATE;

        return $userId.'/'.$visibility;
    }

    /**
     * Build a full object key under the user's visibility tier.
     */
    public static function path(int $userId, string $visibility, string ...$segments): string
    {
        return collect([self::prefix($userId, $visibility), ...$segments])
            ->filter(fn (string $segment) => $segment !== '')
            ->implode('/');
    }

    /**
     * Store an uploaded file and return disk/path metadata for persistence.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public static function putFile(
        UploadedFile $file,
        int $userId,
        string $visibility = self::PRIVATE,
        string $directory = 'uploads',
    ): array {
        $disk = self::disk();
        $filename = Str::uuid().'_'.self::sanitizeFilename($file->getClientOriginalName());
        $directoryPath = self::path($userId, $visibility, $directory);

        $path = $file->storeAs($directoryPath, $filename, [
            'disk' => $disk,
            'visibility' => $visibility === self::PUBLIC ? 'public' : 'private',
        ]);

        return [
            'disk' => $disk,
            'path' => $path,
            'visibility' => $visibility === self::PUBLIC ? self::PUBLIC : self::PRIVATE,
        ];
    }

    /**
     * Move an existing object to the user's other visibility tier, preserving everything after the
     * tier segment (e.g. 42/private/business-assets/x.pdf -> 42/public/business-assets/x.pdf). Returns
     * the new disk/path/visibility, or the unchanged metadata when it already sits in the target tier.
     *
     * @return array{disk: string, path: string, visibility: string}
     */
    public static function move(int $userId, string $path, string $visibility): array
    {
        $disk = self::disk();
        $target = $visibility === self::PUBLIC ? self::PUBLIC : self::PRIVATE;
        $path = ltrim($path, '/');

        if (self::visibilityFromPath($path) === $target) {
            return ['disk' => $disk, 'path' => $path, 'visibility' => $target];
        }

        // Everything after "{userId}/{tier}/" is preserved; fall back to the basename when the source
        // key isn't in the expected layout.
        $remainder = preg_replace('#^\d+/(public|private)/#', '', $path);
        if ($remainder === $path || $remainder === null || $remainder === '') {
            $remainder = basename($path);
        }

        $newPath = self::path($userId, $target, $remainder);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);
        $adapter->move($path, $newPath);
        $adapter->setVisibility($newPath, $target === self::PUBLIC ? 'public' : 'private');

        return ['disk' => $disk, 'path' => $newPath, 'visibility' => $target];
    }

    /**
     * Public URL when the object lives under {userId}/public/; null for private keys.
     */
    public static function publicUrl(string $path): ?string
    {
        if (! self::isPublicPath($path)) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $adapter */
        $adapter = Storage::disk(self::disk());

        return $adapter->url($path);
    }

    public static function isPublicPath(string $path): bool
    {
        return (bool) preg_match('#^\d+/public/#', ltrim($path, '/'));
    }

    public static function visibilityFromPath(string $path): string
    {
        return self::isPublicPath($path) ? self::PUBLIC : self::PRIVATE;
    }

    private static function sanitizeFilename(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name);

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : 'file';
    }
}
