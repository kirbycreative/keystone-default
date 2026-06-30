<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-includes.head :title="page()->title" />

<body id="{{ page()->id }}" class="{{ page()->class() }}">

    <main id="main">
        {{-- Region: content (page body). $slot is one-off page markup; the content
             region holds the staged page sections. --}}
        <div id="content">
            {{ $slot ?? '' }}
        </div>
    </main>

    {{-- Deferred scripts --}}
    @vite(page()->get('vite'))
</body>

</html>
