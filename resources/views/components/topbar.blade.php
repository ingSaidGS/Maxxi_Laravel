@php
    // Enlaces del sistema que se muestran cuando hay sesión iniciada.
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['label' => 'Unidades', 'href' => route('units.index'), 'active' => request()->routeIs('units.*')],
        ['label' => 'Productos', 'href' => route('products.index'), 'active' => request()->routeIs('products.*') || request()->routeIs('product-presentations.*')],
        ['label' => 'Ventas', 'href' => route('sales.index'), 'active' => request()->routeIs('sales.*')],
        ['label' => 'Notas de venta', 'href' => route('sale-notes.index'), 'active' => request()->routeIs('sale-notes.*')],
        ['label' => 'Preparación', 'href' => route('sale-preparations.index'), 'active' => request()->routeIs('sale-preparations.*')],
        ['label' => 'Reportes', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
        ['label' => 'Compras', 'href' => route('purchases.index'), 'active' => request()->routeIs('purchases.*')],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-white/10 bg-maxxi-navy text-white shadow-md print:hidden">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {{-- Botón hamburguesa + isotipo --}}
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open"
                aria-label="Abrir menú de navegación"
                class="inline-flex items-center justify-center rounded-md p-2 text-white/90 transition hover:bg-white/10 hover:text-maxxi-orange focus:outline-none focus:ring-2 focus:ring-maxxi-orange"
            >
                <svg x-show="! open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-cloak x-show="open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center">
                <span class="flex h-10 items-center rounded-lg bg-white px-2 shadow-sm ring-1 ring-black/5">
                    <img src="{{ asset('image/logo/MaxxiIso.png') }}" alt="{{ config('app.name', 'Maxxi') }}" class="h-7 w-auto">
                </span>
            </a>
        </div>

        {{-- Enlaces del sistema (escritorio) --}}
        @auth
            <div class="hidden items-center gap-1 md:flex">
                @foreach ($navLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-white/10 text-maxxi-orange' => $link['active'],
                            'text-white/85 hover:bg-white/5 hover:text-maxxi-orange' => ! $link['active'],
                        ])
                    >{{ $link['label'] }}</a>
                @endforeach
            </div>
        @endauth

        {{-- Usuario / acceso --}}
        <div class="flex items-center gap-2">
            @auth
                <div x-data="{ userOpen: false }" class="relative">
                    <button
                        type="button"
                        @click="userOpen = !userOpen"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10 hover:text-maxxi-orange focus:outline-none focus:ring-2 focus:ring-maxxi-orange"
                    >
                        <span class="hidden max-w-[10rem] truncate sm:inline">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="userOpen"
                        @click.outside="userOpen = false"
                        x-transition
                        class="absolute right-0 mt-2 w-48 overflow-hidden rounded-md bg-white py-1 text-gray-700 shadow-lg ring-1 ring-black/5"
                    >
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm transition hover:bg-maxxi-cream">Perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm transition hover:bg-maxxi-cream">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="rounded-md bg-maxxi-orange px-3 py-2 text-sm font-semibold text-white transition hover:bg-maxxi-orange-dark">Iniciar sesión</a>
            @endauth
        </div>
    </nav>

    {{-- Panel desplegable (menú) --}}
    <div
        x-cloak
        x-show="open"
        x-transition
        class="border-t border-white/10 bg-maxxi-navy"
    >
        <div class="space-y-1 px-4 py-3 sm:px-6">
            @auth
                @foreach ($navLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @class([
                            'block rounded-md px-3 py-2 text-base font-medium transition',
                            'bg-white/10 text-maxxi-orange' => $link['active'],
                            'text-white/85 hover:bg-white/5 hover:text-maxxi-orange' => ! $link['active'],
                        ])
                    >{{ $link['label'] }}</a>
                @endforeach

                <div class="mt-3 border-t border-white/10 pt-3">
                    <p class="px-3 pb-1 text-xs uppercase tracking-wide text-white/50">{{ auth()->user()->name }}</p>
                    <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 text-base font-medium text-white/85 transition hover:bg-white/5 hover:text-maxxi-orange">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-white/85 transition hover:bg-white/5 hover:text-maxxi-orange">Cerrar sesión</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="block rounded-md px-3 py-2 text-base font-medium text-white/85 transition hover:bg-white/5 hover:text-maxxi-orange">Iniciar sesión</a>
            @endauth
        </div>
    </div>
</header>
