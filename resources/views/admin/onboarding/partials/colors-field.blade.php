@php($colors = is_array($value ?? null) ? $value : [])
<div class="field">
    <label>{{ $label ?? 'Primary colors' }}</label>
    <p class="muted font-size:0o8 margin:top:0 margin:bottom:0o5">
        Detected from your logo. Adjust any swatch, or add your own.
    </p>
    <div id="primary-colors" class="color-swatches">
        @foreach ($colors as $color)
            <span class="color-swatch">
                <input type="color" name="{{ $name ?? 'primary_colors' }}[]" value="{{ $color }}">
                <button type="button" class="color-remove" aria-label="Remove color">&times;</button>
            </span>
        @endforeach
    </div>
    <button type="button" id="add-color" class="btn btn--ghost btn--sm margin:top:0o5">Add color</button>
</div>
