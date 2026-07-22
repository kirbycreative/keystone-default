<?php page()->id = 'login'; ?>

<x-layouts.kc-default title="Login">
    <div class="account-login-page">
        <section class="account-login-card">
            <p class="eyebrow">Keystone account</p>
            <h1>Sign in.</h1>

            <form class="lead-form admin-login-form" method="POST" action="{{ route('login.store') }}">
                @csrf

                @if (session('status'))
                    <div class="form-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="form-error-box">{{ $errors->first() }}</div>
                @endif

                <input-text name="email" type="email" label="Email" value="{{ old('email') }}" autocomplete="email" validation="required|email" autofocus></input-text>
                <input-text name="password" type="password" label="Password" autocomplete="current-password" required></input-text>
                <input-checkbox name="remember" label="Remember this browser" value="1"></input-checkbox>
                <input-button class="form-button" type="submit" label="Log In"></input-button>
                <a href="{{ route('password.request') }}">Reset your password</a>
            </form>
        </section>
    </div>
</x-layouts.kc-default>
