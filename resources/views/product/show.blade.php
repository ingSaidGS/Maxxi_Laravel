@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'secundario-sm' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino-sm' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        'peligro-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'exito-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
    ];

    $purchasePresentation = $product->presentations
        ->first(fn ($presentation) => $presentation->purchase_enable && $presentation->active);
    $purchaseConversionFactor = (int) ($purchasePresentation?->conversion_factor ?? 0);
    $purchaseCost = $purchasePresentation?->reference_purchase_cost;

    // Formatea montos sin decimales innecesarios: 200 -> "200", 24.5 -> "24.5", 24.05 -> "24.05".
    $formatAmount = function ($value): ?string {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    };

    $unitPurchasePrice = $purchasePresentation !== null && $purchaseConversionFactor > 0 && $purchaseCost !== null
        ? $formatAmount((float) $purchaseCost / $purchaseConversionFactor)
        : null;

    // Pluralización simple en español: vocal final -> +s, consonante -> +es.
    $pluralize = fn (string $name, int $count): string => $count === 1
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    // Desglose del stock: paquetes de la presentación de compra + unidades sueltas.
    $stock = max(0, (int) $product->stock);
    $stockLabel = (string) $stock;

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

        $stockLabel = $stockParts !== []
            ? implode(' y ', $stockParts)
            : '0 '.$pluralize($product->baseUnit->name, 0);
    } elseif ($product->baseUnit !== null) {
        $stockLabel = $stock.' '.$pluralize($product->baseUnit->name, $stock);
    }

    // Valores iniciales del formulario de stock: un 0 se muestra vacío para que
    // solo se vea el placeholder (evita tener que borrar el cero). Al enviar
    // vacío, el controlador lo interpreta como 0.
    $stockPackagesValue = old('stock_packages', $purchaseConversionFactor > 1 ? intdiv($stock, $purchaseConversionFactor) : 0);
    $stockLooseValue = old('stock_loose', $purchaseConversionFactor > 1 ? $stock % $purchaseConversionFactor : $stock);
    $stockPackagesInput = (int) $stockPackagesValue === 0 ? '' : (string) $stockPackagesValue;
    $stockLooseInput = (int) $stockLooseValue === 0 ? '' : (string) $stockLooseValue;
@endphp

<x-layouts.sistema title="Producto: {{ $product->name }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mb-0 text-2xl font-semibold text-gray-800">{{ $product->name }}</h1>

                @if ($product->active)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                @endif
            </div>
            <p class="mb-0 mt-1 text-sm text-gray-500">Producto #{{ $product->id }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('products.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
            @can('manage-catalog')
                <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Editar
                </a>
            @endcan
        </div>
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

    {{-- Ficha de detalle --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <dl class="mb-0 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Nombre</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $product->name }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Unidad base</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($product->baseUnit)
                        {{ $product->baseUnit->name }}
                        <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $product->baseUnit->symbol }}</span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
                <dd class="mt-1">
                    @if ($product->active)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Stock</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $stockLabel }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Precio de compra unitario</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $unitPurchasePrice !== null ? $unitPurchasePrice.' Bs.' : '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Presentación de compra</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($purchasePresentation && $purchasePresentation->unit && $product->baseUnit)
                        {{ $purchasePresentation->unit->name }} de {{ $purchasePresentation->conversion_factor }} {{ $product->baseUnit->name }} a {{ $formatAmount($purchasePresentation->reference_purchase_cost) }} bs
                    @else
                        —
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creado</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $product->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizado</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $product->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>

            @if ($purchasePresentation)
                @can('manage-catalog')
                    <div>
                        <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Operaciones</dt>
                        <dd class="mt-1">
                            <button type="button" id="stock-toggle"
                                    class="{{ $botones['secundario-sm'] }}"
                                    aria-expanded="false" aria-controls="stock-form">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Actualizar stock
                            </button>
                        </dd>
                    </div>
                @endcan
            @endif
        </dl>
    </div>

    {{-- Formulario de actualización de stock --}}
    @if ($purchasePresentation)
        @can('manage-catalog')
            <div id="stock-form" class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" hidden>
            <h2 class="mb-1 text-lg font-semibold text-gray-800">Actualizar stock</h2>
            <p class="mb-4 text-sm text-gray-500">Indica los paquetes de compra y las unidades sueltas.</p>

            <form action="{{ route('products.stock.update', $product) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($purchaseConversionFactor > 1)
                        <div>
                            <label for="stock_packages" class="mb-1 block text-sm font-medium text-gray-700">{{ $purchasePresentation->unit?->name ?? 'Paquetes' }}</label>
                            <input type="number" id="stock_packages" name="stock_packages" min="0" step="1" placeholder="Ej.: 10"
                                   value="{{ $stockPackagesInput }}"
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                            @error('stock_packages')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="stock_loose" class="mb-1 block text-sm font-medium text-gray-700">
                            {{ $product->baseUnit?->name ?? 'Unidades' }}
                        </label>
                        <input type="number" id="stock_loose" name="stock_loose" min="0" step="1" placeholder="Ej.: 3"
                               value="{{ $stockLooseInput }}"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                        @error('stock_loose')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">Guardar stock</button>
                    <button type="button" id="stock-cancel" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</button>
                </div>
            </form>
            </div>
        @endcan
    @endif

    {{-- Presentaciones del producto --}}
    <div class="mt-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="mb-0 text-lg font-semibold text-gray-800">Presentaciones</h2>

            @can('manage-catalog')
                <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Nueva presentación
                </a>
            @endcan
        </div>

        @if ($product->presentations->isEmpty())
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">Este producto no tiene presentaciones</p>
                <p class="mb-0 text-sm text-gray-500">Crea la primera presentación para empezar.</p>
                @can('manage-catalog')
                    <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}" class="{{ $botones['primario'] }} mt-4">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Nueva presentación
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
                    <h3 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h3>
                </div>

                {{-- Buscador (filtra en cliente) --}}
                <div class="mb-4">
                    <label for="presentation-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <input type="search" id="presentation-filter-search" autocomplete="off"
                               placeholder="Buscar por unidad, factor, precio o estado…"
                               class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    {{-- Filtro por estado (píldora segmentada) --}}
                    <div class="inline-flex items-center self-start rounded-full border border-gray-200 bg-gray-100 p-1" role="group" aria-label="Filtrar presentaciones por estado">
                        <button type="button" data-presentation-filter="active" aria-pressed="true"
                                class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                            Activas
                            <span class="rounded-full bg-green-100 px-1.5 text-xs font-semibold text-green-700">{{ $product->presentations->where('active', true)->count() }}</span>
                        </button>
                        <button type="button" data-presentation-filter="inactive" aria-pressed="false"
                                class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                            Inactivas
                            <span class="rounded-full bg-gray-200 px-1.5 text-xs font-semibold text-gray-600">{{ $product->presentations->where('active', false)->count() }}</span>
                        </button>
                    </div>

                    <button type="button" data-presentation-clear class="{{ $botones['secundario'] }} self-start sm:self-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>

            <div id="presentation-list">
                {{-- Tabla (escritorio) --}}
                <div class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                    <table class="mb-0 w-full border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide">
                                <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Unidad</th>
                                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Factor</th>
                                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Precio venta</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($product->presentations as $presentation)
                                <tr data-href="{{ route('product-presentations.show', $presentation) }}"
                                    data-presentation-item data-active="{{ $presentation->active ? '1' : '0' }}"
                                    data-search="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '') . ' ' . ($presentation->conversion_factor ?? '') . ' ' . ($presentation->sale_price ?? '') . ' ' . ($presentation->active ? 'activa' : 'inactiva')) }}"
                                    class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-800">
                                        @if ($presentation->unit)
                                            {{ $presentation->unit->name }}
                                            <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $presentation->unit->symbol }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-gray-600">{{ $presentation->conversion_factor }}{{ $product->baseUnit ? ' '.$pluralize($product->baseUnit->name, (int) $presentation->conversion_factor) : '' }}</td>
                                    <td class="px-4 py-3 tabular-nums text-gray-800">{{ $presentation->sale_price !== null ? $formatAmount($presentation->sale_price).' Bs.' : '—' }}</td>
                                    <td data-href-ignore class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('product-presentations.show', $presentation) }}" class="{{ $botones['secundario-sm'] }}">Ver</a>

                                            @can('manage-catalog')
                                                <a href="{{ route('product-presentations.edit', $presentation) }}" class="{{ $botones['marino-sm'] }}">Editar</a>

                                                @if ($presentation->active)
                                                    <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST"
                                                          data-presentation-action="deactivate" data-presentation-name="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '')) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST"
                                                          data-presentation-action="restore" data-presentation-name="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '')) }}">
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
                    @foreach ($product->presentations as $presentation)
                        <div data-href="{{ route('product-presentations.show', $presentation) }}"
                             data-presentation-item data-active="{{ $presentation->active ? '1' : '0' }}"
                             data-search="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '') . ' ' . ($presentation->conversion_factor ?? '') . ' ' . ($presentation->sale_price ?? '') . ' ' . ($presentation->active ? 'activa' : 'inactiva')) }}"
                             class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                            <div>
                                <p class="mb-0 font-semibold text-gray-800">
                                    @if ($presentation->unit)
                                        {{ $presentation->unit->name }} ({{ $presentation->unit->symbol }})
                                    @else
                                        —
                                    @endif
                                </p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Factor: <span class="font-medium text-gray-800">{{ $presentation->conversion_factor }}{{ $product->baseUnit ? ' '.$pluralize($product->baseUnit->name, (int) $presentation->conversion_factor) : '' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Precio venta: <span class="font-medium text-gray-800">{{ $presentation->sale_price !== null ? $formatAmount($presentation->sale_price).' Bs.' : '—' }}</span></p>
                            </div>

                            <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('product-presentations.show', $presentation) }}" class="{{ $botones['secundario-sm'] }}">Ver</a>

                                @can('manage-catalog')
                                    <a href="{{ route('product-presentations.edit', $presentation) }}" class="{{ $botones['marino-sm'] }}">Editar</a>

                                    @if ($presentation->active)
                                        <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST"
                                              data-presentation-action="deactivate" data-presentation-name="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '')) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                        </form>
                                    @else
                                        <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST"
                                              data-presentation-action="restore" data-presentation-name="{{ trim(($presentation->unit?->name ?? '') . ' ' . ($presentation->unit?->symbol ?? '')) }}">
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

            {{-- Sin resultados para el filtro --}}
            <div id="presentation-filter-empty" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <p class="mb-0 mt-3 text-sm text-gray-500">No hay presentaciones que coincidan con los filtros.</p>
            </div>
        @endif
    </div>

    <script>
        const stockToggle = document.getElementById('stock-toggle');
        const stockCancel = document.getElementById('stock-cancel');
        const stockForm = document.getElementById('stock-form');

        if (stockToggle && stockForm) {
            const setOpen = (open) => {
                stockForm.hidden = !open;
                stockToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            stockToggle.addEventListener('click', () => setOpen(stockForm.hidden));
            stockCancel?.addEventListener('click', () => setOpen(false));
        }
    </script>

    {{-- Modales: confirmar baja y reactivación de presentaciones --}}
    <x-modal name="confirm-presentation-deactivate" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Dar de baja esta presentación?</h2>
                    <p id="confirm-presentation-deactivate-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Dejará de estar disponible, pero podrás restaurarla después.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-presentation-deactivate')">
                    Cancelar
                </button>
                <button type="button" id="confirm-presentation-deactivate-button" class="{{ $botones['peligro-solido'] }}">
                    Sí, dar de baja
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-presentation-restore" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Reactivar esta presentación?</h2>
                    <p id="confirm-presentation-restore-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Volverá a estar disponible en el sistema.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-presentation-restore')">
                    Cancelar
                </button>
                <button type="button" id="confirm-presentation-restore-button" class="{{ $botones['exito-solido'] }}">
                    Sí, reactivar
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        const presentationFilterButtons = document.querySelectorAll('[data-presentation-filter]');
        const presentationItems = document.querySelectorAll('[data-presentation-item]');
        const presentationList = document.getElementById('presentation-list');
        const presentationFilterEmpty = document.getElementById('presentation-filter-empty');
        const presentationSearchInput = document.getElementById('presentation-filter-search');
        const presentationClearButton = document.querySelector('[data-presentation-clear]');

        // Estado seleccionado en la píldora; por defecto, activas.
        let presentationActiveFilter = 'active';

        const applyPresentationFilter = () => {
            const query = (presentationSearchInput?.value || '').trim().toLowerCase();
            let visible = 0;

            presentationItems.forEach((item) => {
                const isActive = item.dataset.active === '1';
                const matchesState = presentationActiveFilter === 'active' ? isActive : !isActive;

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
            presentationList?.classList.toggle('hidden', visible === 0);
            presentationFilterEmpty?.classList.toggle('hidden', visible > 0);

            presentationFilterButtons.forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.presentationFilter === presentationActiveFilter ? 'true' : 'false');
            });
        };

        presentationFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                presentationActiveFilter = button.dataset.presentationFilter;
                applyPresentationFilter();
            });
        });

        presentationSearchInput?.addEventListener('input', applyPresentationFilter);

        presentationClearButton?.addEventListener('click', () => {
            if (presentationSearchInput) {
                presentationSearchInput.value = '';
            }
            presentationActiveFilter = 'active';
            applyPresentationFilter();
        });

        applyPresentationFilter();
    </script>

    {{-- Confirmación de baja/reactivación de presentaciones --}}
    <script>
        (function () {
            var actionForms = document.querySelectorAll('[data-presentation-action]');
            var deactivateSummary = document.getElementById('confirm-presentation-deactivate-summary');
            var restoreSummary = document.getElementById('confirm-presentation-restore-summary');
            var deactivateButton = document.getElementById('confirm-presentation-deactivate-button');
            var restoreButton = document.getElementById('confirm-presentation-restore-button');
            var pendingForm = null;
            var bypassConfirm = false;

            actionForms.forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (bypassConfirm) { return; }

                    event.preventDefault();
                    pendingForm = form;

                    var name = form.dataset.presentationName || '';
                    var suffix = name ? ' «' + name + '»' : '';

                    if (form.dataset.presentationAction === 'deactivate') {
                        if (deactivateSummary) {
                            deactivateSummary.textContent = 'Se dará de baja la presentación' + suffix + '.';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-presentation-deactivate' }));
                    } else {
                        if (restoreSummary) {
                            restoreSummary.textContent = 'Se reactivará la presentación' + suffix + '.';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-presentation-restore' }));
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

            deactivateButton?.addEventListener('click', function () { submitPending('confirm-presentation-deactivate'); });
            restoreButton?.addEventListener('click', function () { submitPending('confirm-presentation-restore'); });
        })();
    </script>
</x-layouts.sistema>
