<x-layouts.admin title="Content Assets">
    <div class="w:container content-layout">
        <section>
            <h1>Upload business assets</h1>
            <div class="flex:row items-wrap align:end justify:space-between gap:1">
                <p class="lead">
                    Add menus, promotions, ads, documents, photos, and other source material. These uploads are stored
                    privately and queued for future page and copy suggestions.
                </p>
                <a href="{{ route('admin.content.review') }}" class="btn btn--ghost btn--sm">Review uploads</a>
            </div>

            <div class="table margin:top:2">
                <div class="table__head grid-12">
                    <div class="span-5">Asset</div>
                    <div class="span-2">Type</div>
                    <div class="span-2">Size</div>
                    <div class="span-2">Status</div>
                    <div class="span-1 text:right">File</div>
                </div>

                @forelse ($assets as $asset)
                    <div class="table__row grid-12">
                        <div class="span-5">
                            <p class="title-strong">{{ $asset->title }}</p>
                            <p class="muted margin:0">{{ $asset->original_filename }}</p>
                            @if ($asset->notes)
                                <p class="muted font-size:0o9 margin:top:0o5 margin:bottom:0">{{ $asset->notes }}</p>
                            @endif
                        </div>
                        <div class="span-2 muted">{{ $assetTypes[$asset->type] ?? $asset->type }}</div>
                        <div class="span-2 muted">{{ $asset->formatted_size }}</div>
                        <div class="span-2">
                            <span class="badge">{{ $asset->ingestion_status }}</span>
                        </div>
                        <div class="span-1 text:right">
                            <a href="{{ route('admin.content.download', $asset) }}" class="link-accent">Download</a>
                        </div>
                    </div>
                @empty
                    <div class="table__empty">
                        Upload the first asset to start building the content source library.
                    </div>
                @endforelse
            </div>

            <div class="margin:top:1">
                {{ $assets->links() }}
            </div>
        </section>

        <aside class="panel">
            <h2 class="margin:0">Add source material</h2>
            <p class="muted margin:top:0o5">
                Drop files to upload them instantly for AI review, or add details manually below.
            </p>

            <div id="asset-dropzone" class="asset-dropzone margin:top:1">
                <p class="muted margin:0">Drag &amp; drop files here, or click to choose.</p>
                <input type="file" id="asset-drop-input" class="hidden" multiple
                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.rtf">
            </div>
            <div id="asset-drop-list" class="asset-dropzone__list"></div>

            <p class="muted font-size:0o8 margin:top:1 margin:bottom:0o5">Or add with details</p>
            {!! $uploadForm->build() !!}
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const zone = document.getElementById('asset-dropzone');
            const input = document.getElementById('asset-drop-input');
            const list = document.getElementById('asset-drop-list');
            const csrf = document.querySelector('meta[name="csrf"]')?.getAttribute('content') || '';
            const url = @json(route('admin.content.drop'));
            if (!zone || !input) return;

            zone.addEventListener('click', () => input.click());
            ['dragover', 'dragenter'].forEach(ev => zone.addEventListener(ev, e => {
                e.preventDefault(); zone.classList.add('is-dragging');
            }));
            ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => {
                e.preventDefault(); zone.classList.remove('is-dragging');
            }));
            zone.addEventListener('drop', e => uploadFiles(e.dataTransfer.files));
            input.addEventListener('change', () => uploadFiles(input.files));

            function uploadFiles(files) {
                Array.from(files).forEach(uploadFile);
            }

            async function uploadFile(file) {
                const row = document.createElement('div');
                row.className = 'asset-dropzone__item';
                row.innerHTML = '<span>' + file.name + '</span><span class="muted">Uploading\u2026</span>';
                list.prepend(row);

                const body = new FormData();
                body.append('file', file);
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body,
                    });
                    if (!res.ok) throw new Error('Upload failed');
                    const data = await res.json();
                    row.querySelector('span:last-child').textContent = data.asset.status || 'Queued';
                } catch (e) {
                    row.classList.add('is-error');
                    row.querySelector('span:last-child').textContent = 'Failed';
                }
            }
        });
    </script>
</x-layouts.admin>
