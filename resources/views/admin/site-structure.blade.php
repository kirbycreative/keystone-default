<x-layouts.admin title="Site structure">
    @php
        $document = $siteVersion['document'] ?? [];
        $pages = $document['pages'] ?? [];
        $checksum = $siteVersion['checksum'] ?? '';
        $canEdit = auth()->user()->canEditSite();
    @endphp

    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Pages and sections</h1>
                <p class="lead">Edit the canonical draft structure. Changes remain private until an owner publishes them.</p>
            </div>
            <span class="badge">{{ $canEdit ? 'Draft editing' : 'View only' }}</span>
        </div>

        @if (session('status'))
            <div class="panel margin:bottom:1">{{ session('status') }}</div>
        @endif

        <div class="stack gap:2">
            <section class="panel">
                <h2 class="margin:top:0">Navigation</h2>
                <p class="muted">Menus, responsive profiles, and destinations are saved as one validated canonical navigation object.</p>
                <form method="POST" action="{{ route('admin.site-structure.navigation') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="checksum" value="{{ $checksum }}">
                    <textarea name="navigation" rows="14" required @disabled(! $canEdit)>{{ json_encode($document['navigation'] ?? ['menus' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                    @if ($canEdit)
                        <button class="btn btn--primary margin:top:1" type="submit">Save navigation</button>
                    @endif
                </form>
            </section>

            @foreach ($pages as $page)
                <section class="panel">
                    <form method="POST" action="{{ route('admin.site-structure.page') }}" class="grid-2 gap:1">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="checksum" value="{{ $checksum }}">
                        <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                        <label>Page title
                            <input name="title" required maxlength="180" value="{{ $page['title'] }}" @disabled(! $canEdit)>
                        </label>
                        <label>Path
                            <input name="path" required value="{{ $page['path'] }}" @disabled(! $canEdit)>
                        </label>
                        <label>Template
                            <input name="template" required value="{{ $page['template'] }}" @disabled(! $canEdit)>
                        </label>
                        <label>Status
                            <select name="status" @disabled(! $canEdit)>
                                <option value="enabled" @selected($page['status'] === 'enabled')>Enabled</option>
                                <option value="disabled" @selected($page['status'] === 'disabled')>Disabled</option>
                            </select>
                        </label>
                        <label>Order
                            <input type="number" min="0" name="order" required value="{{ $page['order'] }}" @disabled(! $canEdit)>
                        </label>
                        @if ($canEdit)
                            <div class="flex:row gap:1 align:end">
                                <button class="btn btn--primary" type="submit">Save page</button>
                            </div>
                        @endif
                    </form>

                    <h3>Sections</h3>
                    @foreach ($page['sections'] as $section)
                        <form method="POST" action="{{ route('admin.site-structure.section') }}" class="panel margin:bottom:1">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="checksum" value="{{ $checksum }}">
                            <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                            <input type="hidden" name="section_id" value="{{ $section['id'] }}">
                            <div class="grid-2 gap:1">
                                <label>Name <input name="name" required value="{{ $section['name'] }}" @disabled(! $canEdit)></label>
                                <label>Type <input name="type" required value="{{ $section['type'] }}" @disabled(! $canEdit)></label>
                                <label>Template <input name="template" required value="{{ $section['template'] }}" @disabled(! $canEdit)></label>
                                <label>Order <input type="number" min="0" name="order" required value="{{ $section['order'] }}" @disabled(! $canEdit)></label>
                            </div>
                            <label class="flex:row gap:0o5 align:center">
                                <input type="checkbox" name="enabled" value="1" @checked($section['enabled']) @disabled(! $canEdit)>
                                Enabled
                            </label>
                            @php($contentFieldId = 'section-content-'.$section['id'])
                            <label>Structured content
                                <textarea id="{{ $contentFieldId }}" name="content" rows="8" required @disabled(! $canEdit)>{{ json_encode($section['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                            </label>
                            @if ($canEdit)
                                <div class="flex:row gap:1">
                                    <button class="btn btn--primary" type="submit">Save section</button>
                                    <media-library-picker
                                        browse-url="{{ route('admin.media-library.index') }}"
                                        upload-url="{{ route('admin.media-library.store') }}"
                                        target="#{{ $contentFieldId }}"
                                        selection-mode="insert"
                                        button-label="Insert image">
                                    </media-library-picker>
                                </div>
                            @endif
                        </form>
                    @endforeach
                </section>
            @endforeach

            @if ($canEdit)
                <section class="panel">
                    <h2 class="margin:top:0">Add page</h2>
                    <form method="POST" action="{{ route('admin.site-structure.page') }}" class="grid-2 gap:1">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="checksum" value="{{ $checksum }}">
                        <label>Page title <input name="title" required maxlength="180"></label>
                        <label>Path <input name="path" required placeholder="/about"></label>
                        <label>Template <input name="template" required value="default"></label>
                        <label>Status
                            <select name="status">
                                <option value="enabled">Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </label>
                        <label>Order <input type="number" min="0" name="order" required value="{{ count($pages) }}"></label>
                        <div class="flex:row align:end"><button class="btn btn--primary" type="submit">Add page</button></div>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-layouts.admin>
