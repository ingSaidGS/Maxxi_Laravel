@props(['title' => null, 'contenido' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · '.config('app.name', 'Maxxi') : config('app.name', 'Maxxi') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col bg-gray-100">
            <x-topbar />

            <main @class([
                'flex-1',
                'contenido mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8' => $contenido,
            ])>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
