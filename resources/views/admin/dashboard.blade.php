<x-layouts.admin title="Dashboard">
    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Build the site from real business material.</h1>
                <p class="lead">
                    Start by uploading menus, promotions, advertisements, photos, and documents. These files become
                    the source material for page structure, copy suggestions, and future content generation.
                </p>
            </div>

            <div class="flex:row items-wrap gap:0o5">
                <a href="{{ route('admin.content.index') }}" class="btn btn--primary">Upload assets</a>
                <a href="{{ route('admin.content.review') }}" class="btn btn--ghost">Review uploads</a>
            </div>
        </div>

        <div class="grid-3 margin:top:2">
            <div class="stat">
                <p class="stat__label">Uploaded assets</p>
                <p class="stat__value">{{ $assetCount }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">Ingestion queue</p>
                <p class="stat__value">{{ $latestAssets->where('ingestion_status', 'pending')->count() }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">Next step</p>
                <a href="{{ route('admin.content.review') }}" class="link-accent font-size:1o2 block margin:top:0o5">
                    Review uploads and generate page suggestions.
                </a>
            </div>
        </div>

        <section class="panel margin:top:2">
            <div class="flex:row align:center justify:space-between gap:1">
                <h2 class="margin:0">Latest content assets</h2>
                <a href="{{ route('admin.content.index') }}" class="link-accent">View all</a>
            </div>

            <div class="stack margin:top:1">
                @forelse ($latestAssets as $asset)
                    <div class="flex:row items-wrap align:center justify:space-between gap:0o5 padding:top:1 padding:bottom:1">
                        <div>
                            <p class="title-strong">{{ $asset->title }}</p>
                            <p class="muted font-size:0o9 margin:0">{{ \App\Models\ContentAsset::types()[$asset->type] ?? $asset->type }} &middot; {{ $asset->original_filename }}</p>
                        </div>
                        <span class="badge">{{ $asset->ingestion_status }}</span>
                    </div>
                @empty
                    <p class="muted padding:top:2 padding:bottom:2">No business assets uploaded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.admin>
