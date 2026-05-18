<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Roni5' }} — Roni5</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-white">
    <x-storefront.nav />

    <main id="main" class="min-h-[60vh]">
        {{ $slot }}
    </main>

    <x-storefront.footer />

    @livewireScripts
</body>
</html>
