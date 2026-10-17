<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Maxxi') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('image/logo/MaxxiIso.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col bg-gradient-to-b from-maxxi-navy to-maxxi-navy-dark">
            <x-topbar />

            <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6">
                <div class="w-full max-w-md">
                    {{-- Marca --}}
                    <div class="mb-6 flex flex-col items-center text-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white p-2.5 shadow-lg ring-1 ring-black/5">
                            <img src="{{ asset('image/logo/MaxxiIso.png') }}" alt="{{ config('app.name', 'Maxxi') }}" class="h-full w-auto object-contain">
                        </span>
                        <p class="mb-0 mt-3 text-2xl font-bold tracking-tight text-white">Maxxi</p>
                        <p class="mb-0 text-sm text-white/70">Inventario y ventas</p>
                    </div>

                    {{-- Tarjeta --}}
                    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-xl sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
