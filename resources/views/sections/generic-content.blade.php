<section id="{{ $settings['id'] ?? ($section['id'] ?? 'content') }}" class="section generic-content-section">
    <div class="container">
        @foreach ($content as $key => $value)
            @if (is_string($value) && $value !== '')
                @if (in_array($key, ['heading', 'title'], true))
                    <h2>{{ $value }}</h2>
                @else
                    <p>{{ $value }}</p>
                @endif
            @elseif (is_array($value))
                @foreach ($value as $item)
                    @if (is_string($item))<p>{{ $item }}</p>@endif
                @endforeach
            @endif
        @endforeach
    </div>
</section>
