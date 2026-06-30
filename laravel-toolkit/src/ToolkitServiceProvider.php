<?php

namespace Keystone\Toolkit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Keystone\Toolkit\Console\GenerateJsModels;

class ToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Toolkit-owned OpenRouter config so every consuming app gets sensible defaults without
        // editing its own config/services.php. Apps override via the OPENROUTER_* env keys, or by
        // publishing this file (tag: keystone-toolkit-config).
        $this->mergeConfigFrom(__DIR__ . '/../config/openrouter.php', 'openrouter');
        $this->mergeConfigFrom(__DIR__ . '/../config/client-assets.php', 'keystone.client_assets');
        $this->mergeConfigFrom(__DIR__ . '/../config/models.php', 'keystone.models');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'toolkit');

        // Generic model API consumed by the juice ApiDatabase driver.
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateJsModels::class,
            ]);
        }

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

        $this->publishes([
            __DIR__ . '/../config/openrouter.php' => config_path('openrouter.php'),
            __DIR__ . '/../config/client-assets.php' => config_path('keystone/client-assets.php'),
            __DIR__ . '/../config/models.php' => config_path('keystone/models.php'),
        ], 'keystone-toolkit-config');
    }
}
