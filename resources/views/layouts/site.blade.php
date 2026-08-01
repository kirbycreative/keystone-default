<!DOCTYPE html>
<html lang="{{ data_get($document, 'identity.locale', 'en-US') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metadata['title'] }}</title>
    @if ($metadata['description'])<meta name="description" content="{{ $metadata['description'] }}">@endif
    <link rel="canonical" href="{{ $metadata['canonical_url'] }}">
    @if ($metadata['noindex'])<meta name="robots" content="noindex">@endif
    @foreach ($metadata['open_graph'] as $property => $content)
        @if (is_scalar($content) && $content !== '')<meta property="og:{{ str_replace('_', ':', $property) }}" content="{{ $content }}">@endif
    @endforeach
    @foreach ($metadata['structured_data'] as $structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
    @vite(['resources/css/style-guide-variables.css', 'resources/css/auto.compiled.css', 'vendor/keystone/admin/resources/scss/site/base.scss', 'resources/scss/sections.scss', 'resources/js/app.js'])
</head>
<body>
    @if ($preview)
        <div class="panel"><strong>Draft preview</strong> <span class="muted">Version {{ $siteVersion['version'] ?? 'new' }} is not public.</span></div>
    @endif
    <main>@yield('content')</main>

    @php($analytics = data_get($document, 'settings.features.analytics', []))
    @if (($analytics['enabled'] ?? false) && ($analytics['provider'] ?? null) === 'plausible' && ($analytics['measurement_id'] ?? null))
        <script defer data-domain="{{ $analytics['measurement_id'] }}" src="https://plausible.io/js/script.js"></script>
    @elseif (($analytics['enabled'] ?? false) && ($analytics['provider'] ?? null) === 'google-analytics' && ($analytics['measurement_id'] ?? null))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($analytics['measurement_id']) }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date);gtag('config',@json($analytics['measurement_id']));</script>
    @endif
</body>
</html>
