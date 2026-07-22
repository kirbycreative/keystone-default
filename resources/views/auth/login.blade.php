<?php page()->id = 'login'; ?>

<x-layouts.kc-default title="Login">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="login-title">
            <img class="auth-logo" src="{{ Vite::asset('resources/images/logo/logo-long-2-lt.png') }}" alt="Keystone">

            <div class="auth-heading">
                <p class="eyebrow">Website dashboard</p>
                <h1 id="login-title">Welcome back</h1>
                <p>Sign in to manage your website.</p>
            </div>

            <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                @csrf

                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                @error('email') <p class="auth-error">{{ $message }}</p> @enderror

                <div class="auth-password-label">
                    <label for="password">Password</label>
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password">

                <label class="auth-remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Keep me signed in</span>
                </label>

                <button class="auth-submit" type="submit">Sign in</button>
            </form>
        </section>
    </main>
</x-layouts.kc-default>
