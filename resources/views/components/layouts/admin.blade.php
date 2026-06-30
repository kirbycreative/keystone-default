<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<x-includes.head :title="page()->title" />

<body id="{{ page()->id }}" class="{{ page()->class() }}">

    <x-admin.header class="layout-component" />

    <main>
        <h1>
            <div class="w:container text:uppercase">{{ page()->title }}</div>
        </h1>
        <div id="content-container" class="container">
            @if (page()->has('sidebar'))
                <aside id="sidebar">

                </aside>
            @endif
            <div id="content">
                @if (session('status'))
                    <div class="notice notice--success margin:bottom:1">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </main>
</body>

</html>
