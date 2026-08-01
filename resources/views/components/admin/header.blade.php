@php
    $user = auth()->user();
    $onboardingComplete = (bool) $user->onboarded;
@endphp

<header {{ $attributes }}>
    <div class="container">
        <a href="{{ route($onboardingComplete ? 'keystone.admin.dashboard' : 'keystone.admin.onboarding.show') }}" class="logo">
            <img src="{{ Vite::asset('resources/images/logo/logo-long-2-lt.png') }}" height="50" alt="Logo">
        </a>
        @if ($onboardingComplete)
            <nav class="w:100%">
                <ul class="flex:row gap:1rem">
                    <li><a href="{{ route('keystone.admin.dashboard') }}"
                            class="{{ request()->routeIs('keystone.admin.dashboard') ? 'is-active' : '' }}">Dashboard</a></li>
                    @if ($user->onboardingState()->contentUnlocked())
                        <li><a href="{{ route('keystone.admin.content.index') }}"
                                class="{{ request()->routeIs('keystone.admin.content.index') ? 'is-active' : '' }}">Content</a></li>
                        <li><a href="{{ route('keystone.admin.content.review') }}"
                                class="{{ request()->routeIs('keystone.admin.content.review') ? 'is-active' : '' }}">Review</a></li>
                    @endif
                    <li><a href="{{ route('keystone.admin.page-suggestions.index') }}"
                            class="{{ request()->routeIs('keystone.admin.page-suggestions.*') ? 'is-active' : '' }}">Page
                            Suggestions</a></li>
                    <li><a href="{{ route('admin.templates.index') }}"
                            class="{{ request()->routeIs('admin.templates.*') ? 'is-active' : '' }}">Templates</a></li>
                    <li><a href="{{ route('keystone.admin.site-settings.show') }}"
                            class="{{ request()->routeIs('keystone.admin.site-settings.*') ? 'is-active' : '' }}">Site Settings</a></li>
                    <li><a href="{{ route('keystone.admin.site-structure.show') }}"
                            class="{{ request()->routeIs('keystone.admin.site-structure.*') ? 'is-active' : '' }}">Structure</a></li>
                    <li><a href="{{ route('admin.site-preview') }}">Preview</a></li>
                </ul>
            </nav>
        @endif
        <div id="current-user" class="flex:row align:center gap:1rem">
            <div>
                <p class="signed-in text:nowrap">Signed in as</p>
                <p class="text:nowrap fw-600">{{ $user->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">Log out</button>
            </form>
        </div>
    </div>
</header>
