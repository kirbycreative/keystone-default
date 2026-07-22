<x-layouts.admin title="Dashboard">
    <div class="w:container">
        @if ($onboarding->generation_remote_id && ! $onboarding->contentUnlocked())
            @php
                $stage = $onboarding->generation_stage ?: 'style_guide_generation';
                $stageIndex = match ($stage) {
                    'page_tree_generation', 'page_tree_review' => 1,
                    'content_ready', 'site_build', 'completed' => 2,
                    default => 0,
                };
            @endphp

            <div class="page-head">
                <div>
                    <h1>The hard part is done.</h1>
                    <p class="lead">
                        From here, you only need to review each concept and approve it or tell us what should change.
                        You can monitor progress on this screen. If you log out, you'll return here when you sign back in.
                    </p>
                </div>
                <span class="badge" id="build-status">{{ str_replace('_', ' ', $onboarding->generation_status ?: 'queued') }}</span>
            </div>

            <div class="grid-3 margin:top:2">
                <div class="stat">
                    <p class="stat__label">1. Style guide</p>
                    <p class="stat__value font-size:1o2">
                        {{ $stageIndex > 0 ? 'Approved' : ($stage === 'style_guide_review' ? 'Ready for review' : 'In progress') }}
                    </p>
                    <p class="muted margin:bottom:0">The page tree stays locked until this concept is approved.</p>
                </div>
                <div class="stat">
                    <p class="stat__label">2. Page tree</p>
                    <p class="stat__value font-size:1o2">
                        {{ $stageIndex > 1 ? 'Approved' : ($stageIndex === 1 ? ($stage === 'page_tree_review' ? 'Ready for review' : 'In progress') : 'Locked') }}
                    </p>
                    <p class="muted margin:bottom:0">Content stays locked until the page tree is approved.</p>
                </div>
                <div class="stat">
                    <p class="stat__label">3. Content</p>
                    <p class="stat__value font-size:1o2">{{ $stageIndex >= 2 ? 'Available' : 'Locked' }}</p>
                    <p class="muted margin:bottom:0">Content work opens after the approved page structure is in place.</p>
                </div>
            </div>

            <section class="panel margin:top:2">
                <h2 class="margin:0">Current stage</h2>
                <p class="lead margin:top:0o5 margin:bottom:0" id="build-stage">{{ str_replace('_', ' ', $stage) }}</p>
                <p class="muted margin:top:0o5 margin:bottom:0">
                    Review the current concept below when it is ready.
                </p>

                @if (in_array($stage, ['style_guide_review', 'page_tree_review'], true))
                    @php
                        $decisionRoute = $stage === 'style_guide_review'
                            ? route('admin.onboarding.style-guide-decision')
                            : route('admin.onboarding.page-tree-decision');
                        $concept = $stage === 'style_guide_review'
                            ? data_get($onboarding->generation_result, 'style_guide')
                            : data_get($onboarding->generation_result, 'site_layout.pages');
                    @endphp

                    @if ($concept)
                        <pre class="margin:top:1 overflow:auto">{{ json_encode($concept, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif

                    <div class="flex:row items-wrap gap:1 margin:top:1">
                        <form method="POST" action="{{ $decisionRoute }}">
                            @csrf
                            <input type="hidden" name="decision" value="approve">
                            <button class="btn btn--success" type="submit">Approve concept</button>
                        </form>
                        <form method="POST" action="{{ $decisionRoute }}" class="flex:column gap:0o5">
                            @csrf
                            <input type="hidden" name="decision" value="deny">
                            <label for="concept-feedback">What should change?</label>
                            <textarea id="concept-feedback" name="feedback" rows="4" maxlength="2000" required></textarea>
                            <button class="btn btn--danger" type="submit">Request revision</button>
                        </form>
                    </div>
                @endif
            </section>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const initialStage = @json($stage);
                    const statusUrl = @json(route('admin.build-status'));

                    async function refreshBuildStatus() {
                        try {
                            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                            if (!response.ok) return;
                            const data = await response.json();
                            const submission = data.submission || {};
                            document.getElementById('build-status').textContent = String(submission.status || 'processing').replaceAll('_', ' ');
                            document.getElementById('build-stage').textContent = String(submission.stage || initialStage).replaceAll('_', ' ');
                            if (submission.stage && submission.stage !== initialStage) window.location.reload();
                        } catch (error) {
                            // Keep the last known state visible until the next poll.
                        }
                    }

                    refreshBuildStatus();
                    setInterval(refreshBuildStatus, 8000);
                });
            </script>
        @else
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
        @endif
    </div>
</x-layouts.admin>
