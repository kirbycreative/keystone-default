<x-layouts.admin title="Page Suggestions">
    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Suggested site tree</h1>
                <p class="lead">
                    These pages were generated from reviewed uploads. Use this as the first draft of the site structure
                    before creating page copy and layouts.
                </p>
            </div>

            <a href="{{ route('admin.content.review') }}" class="btn btn--primary">Review uploads</a>
        </div>

        @if ($suggestions->isEmpty())
            <section class="panel panel--lg text:center margin:top:2">
                <h2>No page suggestions yet.</h2>
                <p class="muted margin:x:auto" style="max-width:36rem;">
                    Upload and review business assets first. The generator will create a site tree from the source
                    material you select.
                </p>
                <a href="{{ route('admin.content.review') }}" class="btn btn--primary margin:top:1">Start review</a>
            </section>
        @else
            <div class="suggestions-layout margin:top:2">
                <section class="panel">
                    <h2 class="margin:0">Site tree</h2>
                    <div class="flex:column gap:1 margin:top:1">
                        @foreach ($siteTree as $page)
                            <div class="tree-node">
                                <div class="flex:row align:center justify:space-between gap:0o5">
                                    <div>
                                        <p class="title-strong">{{ $page->title }}</p>
                                        <p class="link-accent margin:0">{{ $page->slug }}</p>
                                    </div>
                                    <span class="badge">root</span>
                                </div>

                                @if ($page->children->isNotEmpty())
                                    <div class="tree-children">
                                        @foreach ($page->children as $child)
                                            <div class="padding:top:0o5 padding:bottom:0o5">
                                                <p class="fw-600 margin:0">{{ $child->title }}</p>
                                                <p class="muted margin:0">/{{ $child->slug }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="flex:column gap:1">
                    @foreach ($suggestions as $suggestion)
                        <article class="panel">
                            <div class="flex:row items-wrap align:start justify:space-between gap:1">
                                <div>
                                    <p class="eyebrow">{{ $suggestion->parent_id ? 'Child page' : 'Root page' }}</p>
                                    <h2 class="margin:0">{{ $suggestion->title }}</h2>
                                    <p class="muted margin:top:0o3 margin:bottom:0">
                                        {{ $suggestion->slug === '/' ? '/' : '/' . $suggestion->slug }}</p>
                                </div>
                                <span class="badge">{{ $suggestion->status }}</span>
                            </div>

                            <p class="margin:top:1">{{ $suggestion->summary }}</p>
                            <p class="muted">{{ $suggestion->rationale }}</p>

                            @if (!empty($suggestion->suggested_copy['sections']))
                                <div class="margin:top:1">
                                    <p class="fw-600 margin:bottom:0o5">Suggested sections</p>
                                    <div class="chips">
                                        @foreach ($suggestion->suggested_copy['sections'] as $section)
                                            <span class="chip">{{ $section }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($suggestion->rejection_feedback)
                                <div class="notice notice--error margin:top:1">
                                    <p class="fw-600 margin:0">Denial feedback</p>
                                    <p class="margin:top:0o5 margin:bottom:0">{{ $suggestion->rejection_feedback }}</p>
                                </div>
                            @endif

                            <div class="divider-top margin:top:1 padding:top:1">
                                <div class="flex:row items-wrap align:start justify:space-between gap:1">
                                    {!! $reviewForms[$suggestion->id]['approve']->build() !!}

                                    <details class="panel" style="padding:1rem;">
                                        <summary class="fw-600" style="cursor:pointer;color:#f3a0a2;">
                                            Deny suggestion
                                        </summary>
                                        {!! $reviewForms[$suggestion->id]['reject']->build() !!}
                                    </details>
                                </div>
                            </div>

                            @if ($suggestion->ai_model)
                                <div class="flex:row items-wrap align:center gap:1 margin:top:1 panel"
                                    style="padding:.85rem 1rem;">
                                    <p class="muted margin:0">How did we do? Did this live up to your expectations?</p>
                                    <form method="POST"
                                        action="{{ route('admin.page-suggestions.feedback', $suggestion) }}"
                                        class="flex:row gap:0o5">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" name="feedback" value="yes"
                                            class="btn btn--sm {{ $suggestion->ai_feedback === true ? 'is-on-success' : 'btn--outline-success' }}">
                                            Yes
                                        </button>
                                        <button type="submit" name="feedback" value="no"
                                            class="btn btn--sm {{ $suggestion->ai_feedback === false ? 'is-on-danger' : 'btn--outline-danger' }}">
                                            No
                                        </button>
                                    </form>
                                    @if ($suggestion->ai_feedback !== null)
                                        <span class="muted font-size:0o9">Thanks &mdash; recorded.</span>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </section>
            </div>
        @endif
    </div>
</x-layouts.admin>
