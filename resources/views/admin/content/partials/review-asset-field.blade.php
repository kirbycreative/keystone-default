<label class="table__row grid-12">
    <div class="span-1">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            @checked($checked)
        >
    </div>
    <div class="span-5">
        <p class="title-strong">{{ $asset->title }}</p>
        <p class="muted margin:0">{{ $asset->original_filename }}</p>
        @if ($asset->notes)
            <p class="muted font-size:0o9 margin:top:0o5 margin:bottom:0">{{ $asset->notes }}</p>
        @endif
    </div>
    <div class="span-2 muted">{{ $assetTypes[$asset->type] ?? $asset->type }}</div>
    <div class="span-2">
        <span class="badge">{{ $asset->ingestion_status }}</span>
    </div>
    <div class="span-2 text:right">
        <a href="{{ route('admin.content.download', $asset) }}" class="link-accent">Download</a>
    </div>
</label>
