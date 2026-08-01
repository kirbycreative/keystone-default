<section id="{{ $settings['id'] ?? null }}" class="section proof-section">
    <div class="container">
        @foreach ($content['metrics'] as $metric)<div class="metric"><strong>{{ $metric['value'] }}</strong><span>{{ $metric['label'] }}</span></div>@endforeach
        <h2 class="proof-title">{!! $content['heading'] !!}</h2>
    </div>
</section>
