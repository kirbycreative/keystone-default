<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Keystone\Toolkit\Support\ClientAssetStorage;

class MediaLibraryController extends AdminController
{
    private const IMAGE_EXTENSIONS = ['avif', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'];

    public function index(Request $request): JsonResponse
    {
        $disk = Storage::disk(ClientAssetStorage::disk());
        $assets = collect($disk->allFiles())
            ->filter(fn (string $path): bool => ClientAssetStorage::isPublicPath($path))
            ->filter(fn (string $path): bool => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true))
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'path' => $path,
                'url' => $disk->url($path),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['assets' => $assets]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->canEditSite(), 403);
        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:avif,gif,jpeg,jpg,png,svg,webp', 'max:25600'],
        ]);
        $stored = $request->user()->uploadPublic($validated['file'], 'media');

        return response()->json([
            'asset' => [
                'name' => basename($stored['path']),
                'path' => $stored['path'],
                'url' => Storage::disk($stored['disk'])->url($stored['path']),
            ],
        ], 201);
    }
}
