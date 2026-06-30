<?php

use Illuminate\Support\Facades\Route;
use Keystone\Toolkit\Http\Controllers\ModelController;

/*
| Generic model API consumed by the juice ApiDatabase driver. Registered by the
| ToolkitServiceProvider using the prefix + middleware from config/models.php.
*/

Route::prefix(config('keystone.models.prefix', 'api/model') . '/{model}')
    ->middleware(config('keystone.models.middleware', ['web', 'auth']))
    ->group(function (): void {
        Route::post('/', [ModelController::class, 'create']);
        Route::get('/', [ModelController::class, 'query']);
        Route::get('/{id}', [ModelController::class, 'find']);
        Route::put('/{id}', [ModelController::class, 'update']);
        Route::patch('/{id}', [ModelController::class, 'update']);
        Route::delete('/{id}', [ModelController::class, 'delete']);
    });
