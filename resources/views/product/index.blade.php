@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Productos">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Productos</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Productos registrados y su stock en unidad base.</p>
        </div>

        <a href="{{ route('products.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nuevo producto
        </a>
    </div>

    {{-- Mensaje de éxito --}}
    @if (session('status'))
        <div role="status" class="mb-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
            </svg>
            <div>
                <p class="mb-1 font-semibold">Revisa los siguientes errores:</p>
                <ul class="mb-0 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($products->isEmpty())
        {{-- Estado vacío --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>
            <p class="mb-1 mt-3 font-medium text-gray-700">Aún no hay productos registrados</p>
            <p class="mb-0 text-sm text-gray-500">Crea el primer producto para empezar.</p>
            <a href="{{ route('products.create') }}" class="{{ $botones['primario'] }} mt-4">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nuevo producto
            </a>
        </div>
    @else
        {{-- Tabla (escritorio) --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <table class="mb-0 w-full border-collapse text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide">
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Nombre</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Unidad base</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Stock</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                        <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr data-href="{{ route('products.show', $product) }}"
                            class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($product->baseUnit)
                                    {{ $product->baseUnit->name }}
                                    <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $product->baseUnit->symbol }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums text-gray-800">{{ $product->stock }}</td>
                            <td class="px-4 py-3">
                                @if ($product->active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                                @endif
                            </td>
                            <td data-href-ignore class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('products.show', $product) }}" class="{{ $botones['secundario'] }}">Ver</a>
                                    <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }}">Editar</a>

                                    @if ($product->active)
                                        <form action="{{ route('products.destroy', $product) }}" method="POST"
                                              onsubmit="return confirm('¿Dar de baja este producto? Podrás restaurarlo después.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                        </form>
                                    @else
                                        <form action="{{ route('products.restore', $product) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tarjetas (móvil) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($products as $product)
                <div data-href="{{ route('products.show', $product) }}"
                     class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="mb-0 font-semibold text-gray-800">{{ $product->name }}</p>
                            <p class="mb-0 mt-0.5 text-sm text-gray-500">
                                Unidad base:
                                {{ $product->baseUnit?->name ?? '—' }}@if ($product->baseUnit) ({{ $product->baseUnit->symbol }})@endif
                            </p>
                            <p class="mb-0 mt-0.5 text-sm text-gray-500">Stock: <span class="font-medium text-gray-800">{{ $product->stock }}</span></p>
                        </div>

                        @if ($product->active)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                        @else
                            <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                        @endif
                    </div>

                    <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('products.show', $product) }}" class="{{ $botones['secundario'] }}">Ver</a>
                        <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }}">Editar</a>

                        @if ($product->active)
                            <form action="{{ route('products.destroy', $product) }}" method="POST"
                                  onsubmit="return confirm('¿Dar de baja este producto? Podrás restaurarlo después.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                            </form>
                        @else
                            <form action="{{ route('products.restore', $product) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.sistema>
