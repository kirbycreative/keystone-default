<?php

namespace Keystone\Toolkit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ToolkitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'toolkit');

        Blade::anonymousComponentPath(
            __DIR__ . '/../resources/views/components',
            'toolkit'
        );

        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/keystone-toolkit/css'),
            __DIR__ . '/../resources/fonts' => public_path('vendor/keystone-toolkit/fonts'),
        ], 'keystone-toolkit-assets');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/toolkit'),
        ], 'keystone-toolkit-views');
    }
}
