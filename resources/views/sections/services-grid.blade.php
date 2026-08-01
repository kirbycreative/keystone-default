<section id="{{ $settings['id'] ?? 'services' }}" class="section">
    <div class="services-particles" aria-hidden="true">
        <div class="services-particle-plane">
            @php
                $particleColumns = $settings['particle_columns'] ?? 15;
                $particleRows = $settings['particle_rows'] ?? 7;
            @endphp
            @for ($i = 0; $i < $particleColumns * $particleRows; $i++)
                @php
                    $column = $i % $particleColumns;
                    $row = intdiv($i, $particleColumns);
                    $x = 4 + (($column * 29 + $row * 41 + $i * 13) % 92);
                    $y = 4 + (($column * 43 + $row * 23 + $i * 17) % 92);
                    $planeX = -8 + $column * (116 / max(1, $particleColumns - 1));
                    $planeY = 14 + $row * (72 / max(1, $particleRows - 1));
                    $depth = $particleRows > 1 ? $row / ($particleRows - 1) : 1;
                    $size = 2 + (($i + $row) % 4);
                @endphp
                <span class="services-particle" data-x="{{ $x }}" data-y="{{ $y }}" data-plane-x="{{ $planeX }}"
                    data-plane-y="{{ $planeY }}" data-depth="{{ $depth }}" data-row="{{ $row }}"
                    data-column="{{ $column }}" style="--dot-size: {{ $size }}px; left: {{ $x }}%; top: {{ $y }}%;"></span>
            @endfor
        </div>
    </div>
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">{{ $content['eyebrow'] }}</p>
            <h2>{!! $content['heading'] !!}</h2>
            <p class="text:light">{!! $content['body'] !!}</p>
        </div>
        <div class="service-grid">
            @foreach ($content['items'] as $item)
                <article class="card service">
                    <span class="service-icon">{{ $item['number'] }}</span>
                    <div class="service-copy"><h3>{{ $item['heading'] }}</h3><p>{{ $item['body'] }}</p></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
