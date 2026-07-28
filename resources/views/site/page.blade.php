<!DOCTYPE html>
<html lang="{{ data_get($document, 'identity.locale', 'en-US') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metadata['title'] }}</title>
    @if ($metadata['description'])
        <meta name="description" content="{{ $metadata['description'] }}">
    @endif
    <link rel="canonical" href="{{ $metadata['canonical_url'] }}">
    @if ($metadata['noindex'])
        <meta name="robots" content="noindex">
    @endif
    @foreach ($metadata['open_graph'] as $property => $content)
        @if (is_scalar($content) && $content !== '')
            <meta property="og:{{ str_replace('_', ':', $property) }}" content="{{ $content }}">
        @endif
    @endforeach
    @foreach ($metadata['structured_data'] as $structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
    @vite(['resources/css/style-guide-variables.css', 'resources/scss/base.scss', 'resources/js/app.js'])
</head>
<body>
    @if ($preview)
        <div class="panel">
            <strong>Draft preview</strong>
            <span class="muted">Version {{ $siteVersion['version'] ?? 'new' }} is not public.</span>
        </div>
    @endif

    <main>
        @foreach ($page['sections'] as $section)
            @continue(! ($section['enabled'] ?? false))
            <section id="{{ $section['id'] }}" data-section-type="{{ $section['type'] }}">
                @foreach (($section['content'] ?? []) as $key => $value)
                    @if (is_string($value) && $value !== '')
                        @if (in_array($key, ['heading', 'title'], true))
                            <h2>{{ $value }}</h2>
                        @else
                            <p>{{ $value }}</p>
                        @endif
                    @elseif (is_array($value))
                        @foreach ($value as $item)
                            @if (is_string($item))
                                <p>{{ $item }}</p>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                @if (in_array($section['type'], ['form', 'contact'], true) && data_get($document, 'settings.features.forms.enabled', false))
                    @if (session('form_status'))
                        <p role="status">{{ session('form_status') }}</p>
                    @endif
                    <form method="POST" action="{{ route('forms.store', $section['id']) }}">
                        @csrf
                        <input type="hidden" name="page_path" value="{{ $page['path'] }}">
                        <label>Name <input name="name" maxlength="180" value="{{ old('name') }}"></label>
                        <label>Email <input type="email" name="email" maxlength="254" required value="{{ old('email') }}"></label>
                        <label>Message <textarea name="message" maxlength="10000" required>{{ old('message') }}</textarea></label>
                        <label hidden>Website <input name="website" tabindex="-1" autocomplete="off"></label>
                        <button type="submit">Send</button>
                    </form>
                @endif
            </section>
        @endforeach
    </main>

    @php($analytics = data_get($document, 'settings.features.analytics', []))
    @if (($analytics['enabled'] ?? false) && ($analytics['provider'] ?? null) === 'plausible' && ($analytics['measurement_id'] ?? null))
        <script defer data-domain="{{ $analytics['measurement_id'] }}" src="https://plausible.io/js/script.js"></script>
    @elseif (($analytics['enabled'] ?? false) && ($analytics['provider'] ?? null) === 'google-analytics' && ($analytics['measurement_id'] ?? null))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($analytics['measurement_id']) }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date);gtag('config',@json($analytics['measurement_id']));</script>
    @endif
</body>
</html>
