<?php page()->id = 'reset-password'; ?>

<x-layouts.kc-default title="Choose your password">
    <div class="account-login-page">
        <section class="account-login-card">
            <p class="eyebrow">Keystone account</p>
            <h1>Choose your password.</h1>
            <p>Create the password you will use to manage this website.</p>

            <form class="lead-form admin-login-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                @if ($errors->any())
                    <div class="form-error-box">{{ $errors->first() }}</div>
                @endif

                <input-text name="email" type="email" label="Email" value="{{ old('email', $email) }}" autocomplete="email" validation="required|email"></input-text>
                <input-text name="password" type="password" label="Password" autocomplete="new-password" required></input-text>
                <input-text name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required></input-text>
                <input-button class="form-button" type="submit" label="Save password"></input-button>
            </form>
        </section>
    </div>
</x-layouts.kc-default>
