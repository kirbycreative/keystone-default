<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('vendor/keystone-toolkit/fonts/proxima-nova/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/keystone-toolkit/css/default.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Document</title>

    <?php
    echo page()->render('css');
    echo page()->render('js');
    ?>

</head>

<body class="juice-scroll">
    <scroll-view>
        <div id="scroll-content">
            <x-toolkit::header />
            <main>
                {{ $slot }}
            </main>
            <x-toolkit::footer />
        </div>
    </scroll-view>
    @foreach (page()->get('vite') as $asset)
        @vite($asset)
    @endforeach

</body>

</html>
