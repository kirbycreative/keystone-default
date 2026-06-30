<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TemplateViewerController extends Controller
{
    private array $templatePaths = [
        'resources/views/components/sections' => 'App Views'
    ];

    public function index(Request $request)
    {
        page()->setTitle('Developer Tools');

        $templates = $this->getAllTemplates();

        // Filter by category if requested
        $category = $request->get('category');
        if ($category) {
            $templates = array_filter($templates, fn($t) => $t['category'] === $category);
        }

        // Search if requested
        $search = $request->get('search');
        if ($search) {
            $templates = array_filter(
                $templates,
                fn($t) =>
                stripos($t['name'], $search) !== false ||
                    stripos($t['path'], $search) !== false ||
                    stripos($t['content'] ?? '', $search) !== false
            );
        }

        // Get categories for filter dropdown
        $categories = array_unique(array_column($templates, 'category'));
        sort($categories);

        return view('template-viewer.index', compact('templates', 'categories', 'category', 'search'));
    }

    public function show($path)
    {
        // Decode the path parameter
        $fullPath = base64_decode($path);

        // Security: ensure path is within allowed directories
        $allowedPaths = array_map(fn($p) => base_path($p), array_keys($this->templatePaths));
        $realFullPath = realpath($fullPath);

        $allowed = false;
        foreach ($allowedPaths as $allowedPath) {
            $realAllowed = realpath($allowedPath);
            if ($realAllowed && str_starts_with($realFullPath, $realAllowed)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed || !$realFullPath || !File::exists($realFullPath)) {
            abort(404, 'Template not found or access denied');
        }

        $content = File::get($realFullPath);
        $relativePath = Str::after($realFullPath, base_path() . DIRECTORY_SEPARATOR);
        $name = basename($realFullPath);

        // Determine category
        $category = 'Unknown';
        foreach ($this->templatePaths as $path => $label) {
            if (str_starts_with($relativePath, $path)) {
                $category = $label;
                break;
            }
        }

        return view('template-viewer.show', compact('content', 'name', 'relativePath', 'category'));
    }

    private function getAllTemplates(): array
    {
        $templates = [];

        foreach ($this->templatePaths as $basePath => $category) {
            $fullBasePath = base_path($basePath);

            if (!File::exists($fullBasePath)) {
                continue;
            }

            $files = File::allFiles($fullBasePath);

            foreach ($files as $file) {
                if (!str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $relativePath = Str::after($file->getRealPath(), base_path() . DIRECTORY_SEPARATOR);
                $name = basename($file->getFilename(), '.blade.php');

                // Read content for search
                $content = File::get($file->getRealPath());

                $templates[] = [
                    'name' => $name,
                    'path' => $relativePath,
                    'full_path' => $file->getRealPath(),
                    'category' => $category,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'content' => $content,
                    'encoded_path' => base64_encode($file->getRealPath()),
                ];
            }
        }

        // Sort by category then name
        usort($templates, function ($a, $b) {
            $catCompare = strcmp($a['category'], $b['category']);
            if ($catCompare !== 0) {
                return $catCompare;
            }
            return strcmp($a['name'], $b['name']);
        });

        return $templates;
    }
}
