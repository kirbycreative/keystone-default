<section id="{{ $settings['id'] ?? 'hero' }}" class="section">
    <div class="container hero-content-container">
        <div class="text-panel">
            <h1 class="text:upper">{{ $content['heading'] }}</h1>
            <h1 class="text:upper hero-word-rotator" data-hero-word-rotator aria-live="polite">
                @foreach ($content['rotating_words'] as $word)
                    <span @class(['hero-word-rotator__word', 'is-active' => $loop->first])>{{ $word }}</span>
                @endforeach
            </h1>
            <p>{{ $content['body'] }}</p>
        </div>
    </div>
    <div class="container hero-logo-container" aria-hidden="true">
        <svg id="kc-logo-ani" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 500 600" preserveAspectRatio="xMinYMid meet">
            <line class="k-line" x2="81.3" y2="-302.9" x1="81.3" y1="857" pathLength="100" style="fill: none; stroke: #fff;  stroke-miterlimit: 10; stroke-width: 65px;" />
            <circle class="line-cover" cx="349.5" cy="298.6" r="214.5" pathLength="100" style="fill: none; stroke: #00bfa6; stroke-miterlimit: 10; stroke-width: 68px;" />
            <path class="blk-path" pathLength="100" d="M704,857c-124.5,19.1-236.6-99.9-227-202,9-95.7-67-151-128.6-151h.7c-113.4,0-205.4-92-205.4-205.4s92-205.4,205.4-205.4h437.7c61.6,0,111.5-49.9,111.5-111.5s-49.9-111.5-111.5-111.5-111.5,49.9-111.5,111.5-23.6,52.7-52.7,52.7-52.7-23.6-52.7-52.7v-188.5" style="fill: none; stroke: #FFFFFF; stroke-miterlimit: 10; stroke-width: 55px;" />
            <circle class="o" cx="344" cy="298.6" r="60" pathLength="100" style="fill: none; stroke: #fff;  stroke-miterlimit: 10; stroke-width: 40px;" />
            <path class="color-path" pathLength="100" d="M-393,181c86-5,237,235.2,452,91,111.4-74.8,165.6-176.9,184-203,41-58,85.3-60,106.5-60,44.4,0,80.3,35.9,80.3,80.3s-36,80.3-80.3,80.3l-.5-.4c-71.4,0-129.4,57.9-129.4,129.4s57.9,129.4,129.4,129.4,129.4-57.9,129.4-129.4v4.6c0-71.4,57.9-129.4,129.4-129.4c71.4,0,129.4,57.9,129.4,129.4s57.9,129.4,129.4,129.4h0c71.4,0,129.4-57.9,129.4-129.4V-158.6" style="fill: none; stroke: #212121; stroke-miterlimit: 10; stroke-width: 53px;" />
        </svg>
    </div>
    <svg id="kc-logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 600 600">
        <defs><clipPath id="logo">
            <path d="M393.6,534.9c-61.9,0-120.3-24.1-164-68-43.7-43.7-68-102.1-68-164s24.1-120.3,68-164c43.7-43.7,102.1-68,164-68v51.9c-99.4,0-180.1,81-180.1,180.1s81,180.1,180.1,180.1v51.9Z" style="fill: #000000;" />
            <path id="c2" d="M393.6,455.7c-84.2,0-152.8-68.6-152.8-152.8s68.6-152.8,152.8-152.8v43.4c-60.1,0-109.4,49-109.4,109.4s49,109.4,109.4,109.4v43.4Z" style="fill: #000000;" />
            <path d="M151.9,302.9c0-17.3,1.8-34,5.3-50.2V65.1h-64.2v463.1h64.2v-175.1c-3.5-16.1-5.3-32.9-5.3-50.2Z" style="fill: #000000;" />
            <path d="M388,379.2c-42.2,0-76.3-34.3-76.3-76.3s34.3-76.3,76.3-76.3,76.3,34.3,76.3,76.3-34.3,76.3-76.3,76.3ZM388,259.5c-24.1,0-43.4,19.7-43.4,43.4s19.7,43.4,43.4,43.4,43.4-19.7,43.4-43.4-19.7-43.4-43.4-43.4Z" style="fill: #000000;" />
            <circle cx="488" cy="356.6" r="19.1" style="fill: #000000;" />
        </clipPath></defs>
    </svg>
</section>
