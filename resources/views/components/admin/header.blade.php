@php
    $user = auth()->user();
    $onboardingComplete = (bool) $user->onboarded;
    $logoutForm = new \Keystone\Toolkit\Forms\Form();
    $logoutForm->formInfo = false;
    $logoutForm
        ->setAction(route('logout'))
        ->setAttributes(['class' => 'inline-flex'])
        ->setSubmit('Log out', [
            'class' => 'btn btn--ghost btn--sm',
        ])
        ->setSchema(['form' => []]);
@endphp

<header {{ $attributes }}>
    <div class="container">
        <a href="{{ route($onboardingComplete ? 'admin.dashboard' : 'admin.onboarding') }}" class="logo">
            <img src="{{ Vite::asset('resources/images/logo/logo-long-2-lt.png') }}" height="50" alt="Logo">
        </a>
        @if ($onboardingComplete)
            <nav class="w:100">
                <ul class="flex:row gap:1">
                    <li><a href="{{ route('admin.dashboard') }}"
                            class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a></li>
                    @if ($user->onboardingState()->contentUnlocked())
                        <li><a href="{{ route('admin.content.index') }}"
                                class="{{ request()->routeIs('admin.content.index') ? 'is-active' : '' }}">Content</a></li>
                        <li><a href="{{ route('admin.content.review') }}"
                                class="{{ request()->routeIs('admin.content.review') ? 'is-active' : '' }}">Review</a></li>
                    @endif
                    <li><a href="{{ route('admin.page-suggestions.index') }}"
                            class="{{ request()->routeIs('admin.page-suggestions.*') ? 'is-active' : '' }}">Page
                            Suggestions</a></li>
                    <li><a href="{{ route('admin.templates.index') }}"
                            class="{{ request()->routeIs('admin.templates.*') ? 'is-active' : '' }}">Templates</a></li>
                </ul>
            </nav>
        @endif
        <div id="current-user" class="flex:row align:center gap:1">
            <div>
                <p class="signed-in text:nowrap">Signed in as</p>
                <p class="text:nowrap fw-600">{{ $user->name }}</p>
            </div>
            {!! $logoutForm->build() !!}
        </div>
    </div>
</header>
