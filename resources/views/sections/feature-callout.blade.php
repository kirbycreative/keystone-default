<section id="{{ $settings['id'] ?? null }}" class="section morphing-gradient-host">
    <x-divider type="waves-opacity" shapeWidth="200%" centerX="25%" height="150px" top="-110px" color="#f4f7fbfa" />
    <x-morphing-gradient />
    <div class="container ai-trial-section">
        <div class="ai-trial-copy">
            <img class="ai-trial-logo" src="{{ Vite::asset($content['image']) }}" alt="{{ $content['image_alt'] }}">
            <p class="eyebrow">{{ $content['eyebrow'] }}</p><h2>{{ $content['heading'] }}</h2><p>{{ $content['body'] }}</p>
        </div>
        <div class="ai-trial-callout">
            <span>{{ $content['callout']['eyebrow'] }}</span><h3>{{ $content['callout']['heading'] }}</h3>
            <p>{{ $content['callout']['body'] }}</p>
            <a class="button primary" href="{{ $content['callout']['url'] }}">{{ $content['callout']['label'] }}</a>
        </div>
    </div>
</section>
