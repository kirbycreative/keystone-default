<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Model API
    |--------------------------------------------------------------------------
    |
    | Drives the generic model endpoint that the juice ApiDatabase driver talks
    | to. The same AppModel `$properties` that build the server forms and rules
    | also validate these requests, so client and server share one source of
    | truth. Nothing is exposed unless it is listed in `aliases` below.
    |
    */

    // URL prefix for the model endpoint, e.g. /api/model/{model}.
    'prefix' => env('TOOLKIT_MODEL_PREFIX', 'api/model'),

    // Middleware applied to the model routes. These are admin/data endpoints,
    // so they ride the session guard + CSRF like the rest of the app.
    'middleware' => ['web', 'auth'],

    /*
    | Whitelist of models the API may touch, keyed by the string the client
    | sends in the URL (the juice model's `tableName`). This is the security
    | boundary: a class is unreachable unless it is registered here.
    |
    |   'onboardings' => \App\Models\Onboarding::class,
    */
    'aliases' => [
        //
    ],

    // When true, a model is only reachable if it has a registered Gate policy.
    // When false, registered + authenticated is enough (a policy is still
    // honoured if one exists).
    'require_policy' => env('TOOLKIT_MODEL_REQUIRE_POLICY', false),

    // Where `php artisan make:js-models` writes generated juice models, and the
    // import path (relative to those files) of the juice Model base class.
    'js_output' => 'resources/js/models',
    'js_base' => '../vendor/juice/data/models/Model.mjs',

];
