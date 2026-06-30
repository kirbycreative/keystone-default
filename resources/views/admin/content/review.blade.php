<x-layouts.admin title="Review Uploads">
    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Choose the source material for page suggestions.</h1>
                <p class="lead">
                    Review uploaded business assets, select the files that should inform the first site map, then generate
                    a suggested page structure and copy direction.
                </p>
            </div>

            <a href="{{ route('admin.content.index') }}" class="btn btn--ghost">Upload more assets</a>
        </div>

        @error('asset_ids')
            <div class="notice notice--error margin:top:1">{{ $message }}</div>
        @enderror

        @if ($assets->isNotEmpty())
            <div class="table margin:top:2">
                <div class="table__head grid-12">
                    <div class="span-1">Use</div>
                    <div class="span-5">Asset</div>
                    <div class="span-2">Type</div>
                    <div class="span-2">Status</div>
                    <div class="span-2 text:right">File</div>
                </div>

                {!! $reviewForm->build() !!}
            </div>

            <div class="panel flex:row items-wrap align:center justify:space-between gap:1 margin:top:1">
                <div>
                    <p class="title-strong">Generate page suggestions</p>
                    <p class="muted margin:0">This creates or refreshes the suggested site tree from the selected uploads.</p>
                </div>
            </div>
        @else
            <div class="table__empty table margin:top:2">
                <p class="muted">There are no uploads to review yet.</p>
                <a href="{{ route('admin.content.index') }}" class="btn btn--primary margin:top:1">Upload source material</a>
            </div>
        @endif
    </div>
</x-layouts.admin>
