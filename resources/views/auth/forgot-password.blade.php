<?php page()->id = 'forgot-password'; ?>

<x-layouts.kc-default title="Set your password">
    <div class="account-login-page">
        <section class="account-login-card">
            <p class="eyebrow">Keystone account</p>
            <h1>Set your password.</h1>
            <p>Enter your account email and we will send a single-use setup link.</p>

            <form class="lead-form admin-login-form" method="POST" action="{{ route('password.email') }}">
                @csrf

                @if (session('status'))
                    <div class="form-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="form-error-box">{{ $errors->first() }}</div>
                @endif

                <input-text name="email" type="email" label="Email" value="{{ old('email') }}" autocomplete="email" validation="required|email" autofocus></input-text>
                <input-button class="form-button" type="submit" label="Send setup link"></input-button>
                <a href="{{ route('login') }}">Return to sign in</a>
            </form>
        </section>
    </div>
</x-layouts.kc-default>
