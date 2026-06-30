<x-layouts.admin title="Template Viewer">
    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Template Viewer</h1>
                <p class="lead">Browse and inspect Blade templates in the project.</p>
            </div>
        </div>

        <div class="grid-3 margin:top:2">
            <div class="stat">
                <p class="stat__label">Total Templates</p>
                <p class="stat__value">{{ count($templates) }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">Categories</p>
                <p class="stat__value">{{ count($categories) }}</p>
            </div>
            <div class="stat">
                <p class="stat__label">App Views</p>
                <p class="stat__value">{{ count(array_filter($templates, fn ($t) => $t['category'] === 'App Views')) }}</p>
            </div>
        </div>

        <div class="panel margin:top:1">
            <form method="GET" class="filters">
                <div class="flex:grow">
                    <label for="search" class="muted font-size:0o9">Search templates</label>
                    <input type="text" id="search" name="search" value="{{ $search ?? '' }}"
                        placeholder="Search by name, path, or content..."
                        class="admin-field margin:top:0o3">
                </div>

                <div class="admin-select-wrap">
                    <label for="category" class="muted font-size:0o9">Category</label>
                    <select id="category" name="category" class="admin-field admin-select margin:top:0o3">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($search || $category)
                    <a href="{{ route('admin.templates.index') }}" class="btn btn--ghost btn--sm">Clear filters</a>
                @endif
            </form>
        </div>

        @if (empty($templates))
            <div class="table__empty table margin:top:1">
                <p class="title-strong">No templates found</p>
                <p class="muted">Try adjusting your search or filter criteria.</p>
            </div>
        @else
            <div class="template-grid margin:top:1">
                @foreach ($templates as $template)
                    @php
                        $preview = Str::limit(
                            strip_tags(
                                preg_replace(
                                    '/\{\{--.*?--\}\}/s',
                                    '',
                                    preg_replace('/<!--.*?-->/s', '', $template['content']),
                                ),
                            ),
                            200,
                        );
                        $sizeKB = number_format($template['size'] / 1024, 1);
                        $modified = \Carbon\Carbon::createFromTimestamp($template['modified'])->diffForHumans();
                    @endphp
                    <article class="template-card">
                        <div class="template-card__body">
                            <span class="badge">{{ $template['category'] }}</span>
                            <h3 class="title-strong margin:top:0o5" title="{{ $template['path'] }}">{{ $template['name'] }}</h3>
                            <p class="muted font-size:0o8 margin:0" title="{{ $template['path'] }}">{{ $template['path'] }}</p>
                            <p class="template-card__preview margin:top:0o5">{{ $preview }}</p>
                            <div class="template-card__meta">
                                <span>{{ $sizeKB }} KB</span>
                                <span>{{ $modified }}</span>
                            </div>
                        </div>
                        <div class="template-card__footer">
                            <a href="{{ route('admin.templates.show', $template['encoded_path']) }}" class="btn btn--primary btn--sm w:100">View</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
