<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? 'Page Title' }}</title>

    <link rel="stylesheet" href="{{ asset('vendor/keystone-toolkit/fonts/proxima-nova/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/keystone-toolkit/css/default.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>
    {{ $slot }}
</body>

</html>
