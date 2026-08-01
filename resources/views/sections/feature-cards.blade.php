<section id="{{ $settings['id'] ?? null }}" class="section ai-section">
    <div class="container">
        <div class="ai-copy">
            <p class="eyebrow">{{ $content['eyebrow'] }}</p><h2>{{ $content['heading'] }}</h2><p>{{ $content['body'] }}</p>
            <div class="hero-actions">@foreach ($content['actions'] as $action)<a class="button {{ $action['style'] }}" href="{{ $action['url'] }}">{{ $action['label'] }}</a>@endforeach</div>
        </div>
        <div class="ai-grid" aria-label="{{ $content['items_label'] }}">
            @foreach ($content['items'] as $item)
                <article class="card ai">
                    <img src="{{ Vite::asset($item['image']) }}" width="150" alt="{{ $item['image_alt'] }}">
                    <div class="ai-card-copy"><h3>{{ $item['heading'] }}</h3><p>{{ $item['body'] }}</p></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
