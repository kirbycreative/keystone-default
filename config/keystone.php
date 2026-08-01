<?php

use App\Services\CanonicalSiteRenderer;

return [
    'site_renderer' => CanonicalSiteRenderer::class,
    'dashboard_route' => 'keystone.admin.dashboard',
    'onboarding_route' => 'keystone.admin.onboarding.show',
    'modules' => [
        'site_structure' => true,
        'site_settings' => true,
        'mfa' => true,
        'managed_admin' => true,
    ],
];
