<form class="grid grid-cols-1 gap-4" {{ $attributes }}>
    <header>
        <h1>Form</h1>
        <x-toolkit::notification />
        @if (count($errors) > 0)
            <div class=""><span class="error-text">This form has errors</span></div>
        @endif
    </header>
    <main>
        {{ $slot }}
    </main>

    <footer>
    </footer>

</form>
