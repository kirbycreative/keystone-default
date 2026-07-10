<x-layouts.default title="Choose your password">
    <main><h1>Choose your password</h1>
    <form method="POST" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email <input type="email" name="email" value="{{ old('email', $email) }}" required></label>
        <label>Password <input type="password" name="password" required></label>
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        <button type="submit">Save password</button>
    </form></main>
</x-layouts.default>
