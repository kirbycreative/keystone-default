<x-layouts.default title="Set your password">
    <main><h1>Set your password</h1><p>Enter your account email and we will send a single-use setup link.</p>
    @if (session('status')) <p>{{ session('status') }}</p> @endif
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email') <p>{{ $message }}</p> @enderror
        <button type="submit">Send setup link</button>
    </form></main>
</x-layouts.default>
