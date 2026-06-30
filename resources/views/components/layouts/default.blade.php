<!DOCTYPE html>
<html lang="en">
<x-includes.head :title="page()->title" />

<body id="{{ page()->id }}" class="{{ page()->class() }}">

    {{-- Region: preheader (announcement bars, etc.) --}}
    @foreach (stage()->getComponents('preheader') as $component)
        @include($component['component'], ['content' => $component['content']])
    @endforeach

    {{-- Region: header (site chrome) --}}
    @foreach (stage()->getComponents('header') as $component)
        @include($component['component'], ['content' => $component['content']])
    @endforeach

    <main id="main">
        {{-- Region: sidebar (optional layout chrome) --}}
        @if (stage()->hasComponent('sidebar'))
            <aside id="sidebar">
                @foreach (stage()->getComponents('sidebar') as $component)
                    @include($component['component'], ['content' => $component['content']])
                @endforeach
            </aside>
        @endif

        {{-- Region: content (page body). $slot is one-off page markup; the content
             region holds the staged page sections. --}}
        <div id="content">
            {{ $slot ?? '' }}
            @foreach (stage()->getComponents('content') as $component)
                @include($component['component'], ['content' => $component['content']])
            @endforeach
        </div>
    </main>

    {{-- Region: footer (site chrome) --}}
    @foreach (stage()->getComponents('footer') as $component)
        @include($component['component'], ['content' => $component['content']])
    @endforeach

    {{-- Deferred scripts --}}
    @vite(page()->get('vite'))
</body>

</html>
