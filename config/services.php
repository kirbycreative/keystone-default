<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Kirby Creative API — the client pulls templates from KC's proprietary catalog through this
    // authenticated API (the client never holds S3 catalog credentials). The token is minted by KC
    // at provisioning and written into this site's .env. Every request also sends APP_URL as the
    // site selector so one KC user can safely own multiple sites.
    'keystone' => [
        'url' => env('KEYSTONE_API_URL'),
        'token' => env('KEYSTONE_API_TOKEN'),
    ],

    // This container's network identity, written into .env at provisioning. Onboarding step 1
    // verifies the client's domain points here by matching either its A record to the IP or its
    // nameservers to the configured list (comma-separated).
    'container' => [
        'ip' => env('CONTAINER_IP'),
        'nameservers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CONTAINER_NAMESERVERS', '')),
        ))),
    ],

];
