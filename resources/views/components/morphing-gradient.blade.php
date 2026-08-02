@props(['id' => 'morphing-gradient-' . uniqid()])

<div class="ai-free-gradient" data-morphing-gradient aria-hidden="true"
    style="--morphing-gradient-filter: url('#{{ $id }}')">
    <svg focusable="false">
        <defs>
            <filter id="{{ $id }}">
                <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                <feColorMatrix in="blur" mode="matrix"
                    values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -8" result="goo" />
                <feBlend in="SourceGraphic" in2="goo" />
            </filter>
        </defs>
    </svg>
    <div class="ai-free-gradient__bubbles">
        @foreach (range(1, 5) as $bubble)
            <div class="ai-free-gradient__bubble ai-free-gradient__bubble--{{ $bubble }}"></div>
        @endforeach
        <div class="ai-free-gradient__bubble ai-free-gradient__bubble--interactive"></div>
    </div>
</div>
