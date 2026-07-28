<x-layouts.admin title="Site settings">
    @php
        $document = $siteVersion['document'] ?? [];
        $identity = $document['identity'] ?? [];
        $seo = $document['seo'] ?? [];
        $features = data_get($document, 'settings.features', []);
        $integrations = $document['integrations'] ?? [];
        $canEdit = auth()->user()->canEditSite();
        $canPublish = auth()->user()->canPublishSite();
    @endphp

    <div class="w:container">
        <div class="page-head">
            <div>
                <h1>Site settings</h1>
                <p class="lead">Manage the identity and search defaults used by the canonical site draft.</p>
            </div>
            <span class="badge">{{ $canEdit ? ucfirst(auth()->user()->role) : 'View only' }}</span>
        </div>

        @if (session('status'))
            <div class="panel margin:bottom:1">{{ session('status') }}</div>
        @endif

        <div class="grid-2 gap:2">
            <section class="panel">
                <h2 class="margin:top:0">Measured usage</h2>
                <p>AI: {{ number_format(data_get($usage, 'ai.total_tokens', 0)) }} tokens
                    · Storage: {{ number_format(data_get($usage, 'storage.bytes', 0) / 1048576, 2) }} MB
                    · Hosting: {{ number_format(data_get($usage, 'hosting.seconds', 0) / 86400, 2) }} days
                    · Premium workflows: {{ number_format(data_get($usage, 'premium_workflows.onboarding_runs', 0) + data_get($usage, 'premium_workflows.layout_runs', 0)) }}</p>
                <p class="muted">Measured {{ data_get($usage, 'measured_at', 'not yet') }}.</p>
            </section>

            <section class="panel">
                <h2 class="margin:top:0">Identity</h2>
                <form method="POST" action="{{ route('admin.site-settings.identity') }}" class="flex:column gap:1">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="checksum" value="{{ $siteVersion['checksum'] ?? '' }}">

                    <label>Site name
                        <input type="text" name="name" maxlength="180" required
                            value="{{ old('name', $identity['name'] ?? '') }}" @disabled(! $canEdit)>
                    </label>
                    <label>Legal name
                        <input type="text" name="legal_name" maxlength="180"
                            value="{{ old('legal_name', $identity['legal_name'] ?? '') }}" @disabled(! $canEdit)>
                    </label>
                    <label>Tagline
                        <textarea name="tagline" maxlength="500" rows="3" @disabled(! $canEdit)>{{ old('tagline', $identity['tagline'] ?? '') }}</textarea>
                    </label>
                    <label>Locale
                        <input type="text" name="locale" maxlength="35" required
                            value="{{ old('locale', $identity['locale'] ?? 'en-US') }}" @disabled(! $canEdit)>
                    </label>
                    <label>Timezone
                        <input type="text" name="timezone" required
                            value="{{ old('timezone', $identity['timezone'] ?? 'America/New_York') }}" @disabled(! $canEdit)>
                    </label>

                    @if ($canEdit)
                        <button type="submit" class="btn btn--primary">Save identity</button>
                    @endif
                </form>
            </section>

            <section class="panel">
                <h2 class="margin:top:0">Search defaults</h2>
                <form method="POST" action="{{ route('admin.site-settings.seo') }}" class="flex:column gap:1">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="checksum" value="{{ $siteVersion['checksum'] ?? '' }}">

                    <label>Title template
                        <input type="text" name="title_template" maxlength="180" required
                            value="{{ old('title_template', $seo['title_template'] ?? '%s') }}" @disabled(! $canEdit)>
                    </label>
                    <label>Default description
                        <textarea name="default_description" maxlength="500" rows="5" @disabled(! $canEdit)>{{ old('default_description', $seo['default_description'] ?? '') }}</textarea>
                    </label>
                    <label class="flex:row align:center gap:0o5">
                        <input type="checkbox" name="sitemap_enabled" value="1"
                            @checked(old('sitemap_enabled', $seo['sitemap_enabled'] ?? true)) @disabled(! $canEdit)>
                        Include the site in its sitemap
                    </label>

                    @if ($canEdit)
                        <button type="submit" class="btn btn--primary">Save search settings</button>
                    @endif
                </form>
            </section>
        </div>

        <div class="grid-2 gap:2 margin:top:2">
            <section class="panel">
                <h2 class="margin:top:0">Forms and analytics</h2>
                <form method="POST" action="{{ route('admin.site-settings.features') }}" class="flex:column gap:1">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="checksum" value="{{ $siteVersion['checksum'] ?? '' }}">
                    <label class="flex:row align:center gap:0o5">
                        <input type="checkbox" name="forms_enabled" value="1"
                            @checked(data_get($features, 'forms.enabled', false)) @disabled(! $canEdit)>
                        Enable public forms
                    </label>
                    <label class="flex:row align:center gap:0o5">
                        <input type="checkbox" name="analytics_enabled" value="1"
                            @checked(data_get($features, 'analytics.enabled', false)) @disabled(! $canEdit)>
                        Enable analytics
                    </label>
                    <label>Analytics provider
                        <input name="analytics_provider" maxlength="80"
                            value="{{ data_get($features, 'analytics.provider') }}" @disabled(! $canEdit)>
                    </label>
                    <label>Measurement ID
                        <input name="analytics_measurement_id" maxlength="120"
                            value="{{ data_get($features, 'analytics.measurement_id') }}" @disabled(! $canEdit)>
                    </label>
                    @if ($canEdit)
                        <button class="btn btn--primary" type="submit">Save feature settings</button>
                    @endif
                </form>
            </section>

            <section class="panel">
                <h2 class="margin:top:0">Integrations</h2>
                <p class="muted">Only non-secret published behavior belongs here. Credentials remain in the protected integration store.</p>
                <form method="POST" action="{{ route('admin.site-settings.integrations') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="checksum" value="{{ $siteVersion['checksum'] ?? '' }}">
                    <textarea name="integrations" rows="12" required @disabled(! $canEdit)>{{ json_encode($integrations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                    @if ($canEdit)
                        <button class="btn btn--primary margin:top:1" type="submit">Save integrations</button>
                    @endif
                </form>
            </section>
        </div>

        <section class="panel margin:top:2">
            <h2 class="margin:top:0">Publication</h2>
            @if ($canPublish)
                <form method="POST" action="{{ route('admin.site-settings.publish') }}" class="flex:column gap:1">
                    @csrf
                    <input type="hidden" name="checksum" value="{{ $siteVersion['checksum'] ?? '' }}">
                    <label>Change summary
                        <textarea name="change_summary" maxlength="500" rows="3" required></textarea>
                    </label>
                    <button type="submit" class="btn btn--primary">Publish this draft</button>
                </form>
            @else
                <p class="muted margin:bottom:0">Only an owner can publish or create a rollback draft.</p>
            @endif

            <h3>Published history</h3>
            <div class="stack">
                @forelse ($publishedVersions as $version)
                    <div class="flex:row items-wrap align:center justify:space-between gap:1">
                        <div>
                            <strong>Version {{ $version['version'] }}</strong>
                            @if ($version['is_current'])
                                <span class="badge">Current</span>
                            @endif
                            <p class="muted margin:0">{{ $version['change_summary'] ?: 'No summary' }}</p>
                        </div>
                        @if ($canPublish && ! $version['is_current'])
                            <form method="POST" action="{{ route('admin.site-settings.rollback', $version['id']) }}">
                                @csrf
                                <button type="submit" class="btn btn--ghost">Create rollback draft</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="muted margin:bottom:0">No site version has been published yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.admin>
