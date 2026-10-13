@php
    // Botones de la portada (fuera del layout contenido, fondo marino).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2 focus:ring-offset-maxxi-navy',
    ];

    // Características principales del sistema (iconos outline de Heroicons).
    $caracteristicas = [
        [
            'titulo' => 'Inventario',
            'texto' => 'Unidades, productos y presentaciones con su costo y precio siempre al día.',
            'd' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        ],
        [
            'titulo' => 'Ventas',
            'texto' => 'Registra ventas al contado o fiadas y controla lo que te deben.',
            'd' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
        ],
        [
            'titulo' => 'Notas y preparación',
            'texto' => 'Agrupa pedidos por fecha y alista la entrega de grandes y pequeños.',
            'd' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        ],
        [
            'titulo' => 'Escáner',
            'texto' => 'Lee códigos de barras con la cámara para vender y comprar más rápido.',
            'd' => 'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15',
        ],
    ];
@endphp

<x-layouts.sistema title="Inicio" :contenido="false">
    <div class="relative flex min-h-[calc(100vh-4rem)] flex-col justify-center overflow-hidden bg-gradient-to-b from-maxxi-navy to-maxxi-navy-dark">
        {{-- Adornos de fondo --}}
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-maxxi-orange/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-maxxi-orange/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                {{-- Texto --}}
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-maxxi-orange">
                        Inventario y ventas
                    </span>

                    <h1 class="mt-4 text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">
                        Todo tu negocio en un solo lugar
                    </h1>

                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/75 lg:mx-0">
                        Maxxi te ayuda a controlar tu inventario, registrar ventas, gestionar notas de entrega y preparar pedidos, con escáner de códigos de barras incluido.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                        @auth
                            <a href="{{ route('dashboard') }}" class="{{ $botones['primario'] }}">
                                Ir al panel
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="{{ $botones['primario'] }}">
                                Iniciar sesión
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- Isotipo --}}
                <div class="flex justify-center lg:justify-end">
                    <div class="w-full max-w-sm rounded-3xl border border-white/10 bg-white/5 p-8 text-center shadow-2xl backdrop-blur">
                        <span class="mx-auto flex h-28 w-28 items-center justify-center rounded-2xl bg-white p-3 shadow-lg ring-1 ring-black/5">
                            <img src="{{ asset('image/logo/MaxxiIso.png') }}" alt="Maxxi" class="h-full w-auto object-contain">
                        </span>
                        <p class="mb-0 mt-5 text-3xl font-extrabold tracking-tight text-white">Maxxi</p>
                        <p class="mb-0 mt-1 text-sm text-white/70">Sistema de inventario y ventas</p>
                    </div>
                </div>
            </div>

            {{-- Características --}}
            <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($caracteristicas as $item)
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['d'] }}" />
                            </svg>
                        </span>
                        <h2 class="mb-0 mt-3 text-base font-semibold text-white">{{ $item['titulo'] }}</h2>
                        <p class="mb-0 mt-1 text-sm leading-relaxed text-white/70">{{ $item['texto'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mb-0 mt-12 text-center text-xs text-white/50">
                Maxxi · Sistema de inventario y ventas
            </p>
        </div>
    </div>
</x-layouts.sistema>
