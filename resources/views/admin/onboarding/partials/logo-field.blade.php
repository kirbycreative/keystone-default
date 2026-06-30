@php($logoUrl = $logoUrl ?? (isset($onboarding) ? $onboarding->logoUrl() : null))
<div class="field">
    <label>{{ $label ?? 'Logo' }}</label>
    <div id="logo-dropzone" class="logo-dropzone" data-has-logo="{{ $logoUrl ? '1' : '0' }}">
        <img id="logo-preview" class="logo-preview {{ $logoUrl ? '' : 'hidden' }}" src="{{ $logoUrl }}" alt="Logo preview">
        <p id="logo-placeholder" class="muted {{ $logoUrl ? 'hidden' : '' }}">
            Drag your logo here, or click to choose. PNG, JPG, WEBP, or SVG.
        </p>
        <input type="file" id="logo-input" name="{{ $name ?? 'logo' }}" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
    </div>
    <canvas id="logo-canvas" class="hidden" width="64" height="64"></canvas>
</div>
