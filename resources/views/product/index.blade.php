@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        'peligro-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'exito-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
    ];

    // Formatea montos sin decimales innecesarios: 200 -> "200", 24.5 -> "24.5", 24.05 -> "24.05".
    $formatAmount = function ($value): ?string {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    };

    // Pluralización simple en español: vocal final -> +s, consonante -> +es.
    $pluralize = fn (string $name, int $count): string => $count === 1
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    // Genera la etiqueta de stock con el mismo formato que la vista show.
    $getStockLabel = function ($product) use ($pluralize): string {
        $stock = max(0, (int) $product->stock);

        $purchasePresentation = $product->presentations
            ->first(fn ($presentation) => $presentation->purchase_enable && $presentation->active);

        $purchaseConversionFactor = (int) ($purchasePresentation?->conversion_factor ?? 0);

        if ($purchaseConversionFactor > 1 && $purchasePresentation?->unit !== null && $product->baseUnit !== null) {
            $stockPackages = intdiv($stock, $purchaseConversionFactor);
            $stockLoose = $stock % $purchaseConversionFactor;
            $stockParts = [];

            if ($stockPackages > 0) {
                $stockParts[] = $stockPackages.' '.$pluralize($purchasePresentation->unit->name, $stockPackages);
            }

            if ($stockLoose > 0) {
                $stockParts[] = $stockLoose.' '.$pluralize($product->baseUnit->name, $stockLoose);
            }

            return $stockParts !== []
                ? implode(' y ', $stockParts)
                : '0 '.$pluralize($product->baseUnit->name, 0);
        }

        if ($product->baseUnit !== null) {
            return $stock.' '.$pluralize($product->baseUnit->name, $stock);
        }

        return (string) $stock;
    };
@endphp

<x-layouts.sistema title="Productos">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Productos</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Productos registrados y su stock en unidad base.</p>
        </div>

        @can('manage-catalog')
            <a href="{{ route('products.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nuevo producto
            </a>
        @endcan
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
            @can('manage-catalog')
                <a href="{{ route('products.create') }}" class="{{ $botones['primario'] }} mt-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Nuevo producto
                </a>
            @endcan
        </div>
    @else
        {{-- Filtros: búsqueda libre + estado --}}
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </span>
                <h2 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h2>
            </div>

            {{-- Buscador (filtra en cliente) --}}
            <div class="mb-4">
                <label for="product-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input type="search" id="product-filter-search" autocomplete="off"
                           placeholder="Buscar por nombre, unidad o estado…"
                           class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                {{-- Filtro por estado (píldora segmentada) --}}
                <div class="inline-flex items-center self-start rounded-full border border-gray-200 bg-gray-100 p-1" role="group" aria-label="Filtrar productos por estado">
                    <button type="button" data-product-filter="active" aria-pressed="true"
                            class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                        Activos
                        <span class="rounded-full bg-green-100 px-1.5 text-xs font-semibold text-green-700">{{ $products->where('active', true)->count() }}</span>
                    </button>
                    <button type="button" data-product-filter="inactive" aria-pressed="false"
                            class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                        Inactivos
                        <span class="rounded-full bg-gray-200 px-1.5 text-xs font-semibold text-gray-600">{{ $products->where('active', false)->count() }}</span>
                    </button>
                </div>

                <button type="button" data-product-clear class="{{ $botones['secundario'] }} self-start sm:self-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </button>
            </div>
        </div>

        <div id="product-list">
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
                                data-product-item data-active="{{ $product->active ? '1' : '0' }}"
                                @php
                                    $stockLabel = $getStockLabel($product);
                                @endphp
                                data-search="{{ trim(($product->name ?? '') . ' ' . ($product->baseUnit?->name ?? '') . ' ' . ($product->baseUnit?->symbol ?? '') . ' ' . ($product->active ? 'activo' : 'inactivo') . ' ' . $stockLabel) }}"
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
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $stockLabel }}</td>
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

                                        @can('manage-catalog')
                                            <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }}">Editar</a>

                                            @if ($product->active)
                                                <form action="{{ route('products.destroy', $product) }}" method="POST"
                                                      data-product-action="deactivate" data-product-name="{{ $product->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                                </form>
                                            @else
                                                <form action="{{ route('products.restore', $product) }}" method="POST"
                                                      data-product-action="restore" data-product-name="{{ $product->name }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                                                </form>
                                            @endif
                                        @endcan
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
                         data-product-item data-active="{{ $product->active ? '1' : '0' }}"
                         @php
                             $stockLabel = $getStockLabel($product);
                         @endphp
                         data-search="{{ trim(($product->name ?? '') . ' ' . ($product->baseUnit?->name ?? '') . ' ' . ($product->baseUnit?->symbol ?? '') . ' ' . ($product->active ? 'activo' : 'inactivo') . ' ' . $stockLabel) }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="mb-0 font-semibold text-gray-800">{{ $product->name }}</p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">
                                    Unidad base:
                                    {{ $product->baseUnit?->name ?? '—' }}@if ($product->baseUnit) ({{ $product->baseUnit->symbol }})@endif
                                </p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Stock: <span class="font-medium text-gray-800">{{ $stockLabel }}</span></p>
                            </div>

                            @if ($product->active)
                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                            @else
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                            @endif
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('products.show', $product) }}" class="{{ $botones['secundario'] }}">Ver</a>

                            @can('manage-catalog')
                                <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }}">Editar</a>

                                @if ($product->active)
                                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                                          data-product-action="deactivate" data-product-name="{{ $product->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                    </form>
                                @else
                                    <form action="{{ route('products.restore', $product) }}" method="POST"
                                          data-product-action="restore" data-product-name="{{ $product->name }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($products->hasPages() || $products->total() > 0)
            <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row">
                <div class="flex flex-col items-center gap-2 sm:flex-row sm:gap-3">
                    <p class="mb-0 text-sm text-gray-500">
                        Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }} de {{ $products->total() }} productos
                    </p>
                    <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2 text-sm text-gray-600">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="per_page" class="font-medium text-gray-700">Mostrar</label>
                        <select id="per_page" name="per_page" onchange="this.form.submit()"
                                class="rounded-lg border-gray-300 py-1 pl-2 pr-8 text-sm shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                            @foreach ([10, 15, 25, 50, 100] as $option)
                                <option value="{{ $option }}" @selected((int) request('per_page', 15) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span>por página</span>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    {{ $products->links() }}
                </div>
            </div>
        @endif

        {{-- Sin resultados para el filtro --}}
        <div id="product-filter-empty" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="mb-0 mt-3 text-sm text-gray-500">No hay productos que coincidan con los filtros.</p>
        </div>
    @endif

    {{-- Modales: confirmar baja y reactivación --}}
    <x-modal name="confirm-product-deactivate" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Dar de baja este producto?</h2>
                    <p id="confirm-product-deactivate-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Dejará de estar disponible, pero podrás reactivarlo después.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-product-deactivate')">
                    Cancelar
                </button>
                <button type="button" id="confirm-product-deactivate-button" class="{{ $botones['peligro-solido'] }}">
                    Sí, dar de baja
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-product-restore" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Reactivar este producto?</h2>
                    <p id="confirm-product-restore-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Volverá a estar disponible en el sistema.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-product-restore')">
                    Cancelar
                </button>
                <button type="button" id="confirm-product-restore-button" class="{{ $botones['exito-solido'] }}">
                    Sí, reactivar
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        const productFilterButtons = document.querySelectorAll('[data-product-filter]');
        const productItems = document.querySelectorAll('[data-product-item]');
        const productList = document.getElementById('product-list');
        const productFilterEmpty = document.getElementById('product-filter-empty');
        const productSearchInput = document.getElementById('product-filter-search');
        const productClearButton = document.querySelector('[data-product-clear]');

        // Estado seleccionado en la píldora; por defecto, activos.
        let productActiveFilter = 'active';

        const applyProductFilter = () => {
            const query = (productSearchInput?.value || '').trim().toLowerCase();
            let visible = 0;

            productItems.forEach((item) => {
                const isActive = item.dataset.active === '1';
                const matchesState = productActiveFilter === 'active' ? isActive : !isActive;

                let matchesSearch = true;
                if (query && !(item.dataset.search || '').toLowerCase().includes(query)) {
                    matchesSearch = false;
                }

                // Un ítem se muestra solo si cumple el estado Y la búsqueda.
                const show = matchesState && matchesSearch;
                item.classList.toggle('hidden', !show);
                if (show) {
                    visible++;
                }
            });

            // Oculta la lista completa (tabla + tarjetas) si el filtro no arroja resultados.
            productList?.classList.toggle('hidden', visible === 0);
            productFilterEmpty?.classList.toggle('hidden', visible > 0);

            productFilterButtons.forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.productFilter === productActiveFilter ? 'true' : 'false');
            });
        };

        productFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                productActiveFilter = button.dataset.productFilter;
                applyProductFilter();
            });
        });

        productSearchInput?.addEventListener('input', applyProductFilter);

        productClearButton?.addEventListener('click', () => {
            if (productSearchInput) {
                productSearchInput.value = '';
            }
            productActiveFilter = 'active';
            applyProductFilter();
        });

        applyProductFilter();
    </script>

    {{-- Confirmación de baja/reactivación --}}
    <script>
        (function () {
            var actionForms = document.querySelectorAll('[data-product-action]');
            var deactivateSummary = document.getElementById('confirm-product-deactivate-summary');
            var restoreSummary = document.getElementById('confirm-product-restore-summary');
            var deactivateButton = document.getElementById('confirm-product-deactivate-button');
            var restoreButton = document.getElementById('confirm-product-restore-button');
            var pendingForm = null;
            var bypassConfirm = false;

            actionForms.forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (bypassConfirm) { return; }

                    event.preventDefault();
                    pendingForm = form;

                    var name = form.dataset.productName || '';

                    if (form.dataset.productAction === 'deactivate') {
                        if (deactivateSummary) {
                            deactivateSummary.textContent = 'Se dará de baja el producto «' + name + '».';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-product-deactivate' }));
                    } else {
                        if (restoreSummary) {
                            restoreSummary.textContent = 'Se reactivará el producto «' + name + '».';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-product-restore' }));
                    }
                });
            });

            function submitPending(modalName) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));

                if (!pendingForm) { return; }

                bypassConfirm = true;

                if (typeof pendingForm.requestSubmit === 'function') {
                    pendingForm.requestSubmit();
                } else {
                    pendingForm.submit();
                }

                bypassConfirm = false;
                pendingForm = null;
            }

            deactivateButton?.addEventListener('click', function () { submitPending('confirm-product-deactivate'); });
            restoreButton?.addEventListener('click', function () { submitPending('confirm-product-restore'); });
        })();
    </script>
</x-layouts.sistema>
