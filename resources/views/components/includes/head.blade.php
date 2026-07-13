<?php
if (!isset($page)) {
    $page = page() ?? [];
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'Client Site') }}</title>

    <meta name="env" content="{{ env('APP_ENV') }}">
    <meta name="storage-bucket" content="{{ env('AWS_PUBLIC_STORAGE_BUCKET') }}">
    <meta name="recapcha-key" content="{{ env('RECAPTCHA_SITE_KEY') }}">
    @if (Auth::user() !== null)
        <meta name="auth-user" content="{{ Auth::user()->id }}">
    @endif

    <!-- Site Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#ffc40d">
    <meta name="msapplication-config" content="/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <!-- END Site Favicons -->

    @if (env('GOOGLE_SITE_VERIFICATION', false))
        <meta name="google-site-verification" content="{{ env('GOOGLE_SITE_VERIFICATION') }}" />
    @endif

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <meta name="env" content="{{ env('APP_ENV') }}" />
    <meta name="csrf" content="{{ csrf_token() }}" />

    <script>
        const app = {
            data: {!! page()->dataJson() !!}
        };
        window.app = app;
    </script>

    <!-- Global JS/CSS -->
    @vite(['resources/css/style-guide-variables.css', 'resources/scss/base.scss', 'resources/js/app.js'])
    @if (request()->is('admin/*'))
        @vite('resources/css/admin.css')
    @endif

    <?php
    // Render page-specific JS/CSS
    echo page()->render('css');
    echo page()->render('js');
    ?>

    <!-- Fonts -->
    <link rel="stylesheet" type="text/css" href="/fonts/proxima-nova/fonts.min.css" />

    <!-- Extra slot for per-page head Additions -->
    {{ $slot }}
</head>
