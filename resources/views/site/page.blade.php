@extends('layouts.site')

@section('content')
    @foreach ($page['sections'] as $section)
        @continue(! ($section['enabled'] ?? false))
        @php($sectionView = 'sections.'.($section['template'] ?? ''))
        @if (view()->exists($sectionView))
            @include($sectionView, [
                'content' => $section['content'] ?? [],
                'settings' => $section['settings'] ?? [],
                'section' => $section,
            ])
        @else
            @php(throw new RuntimeException("Installed section template missing: {$sectionView}"))
        @endif
    @endforeach
@endsection
