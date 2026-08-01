<section class="hero-section">
    <x-divider class="z:10" type="tilt" shapeWidth="100%" height="100px" top="-100px" color="#212121" mirrorY="true" />
    <div class="container">
        <div class="hero-image">
            <img src="{{ Vite::asset($content['image']) }}" alt="{{ $content['image_alt'] }}">
        </div>
        <div class="hero-copy">
            <p class="eyebrow">{{ $content['eyebrow'] }}</p>
            <h1>{{ $content['heading'] }}</h1>
            <p class="hero-text">{{ $content['body'] }}</p>
            <div class="hero-actions">
                @foreach ($content['actions'] as $action)
                    <a class="button {{ $action['style'] }}" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
</section>
