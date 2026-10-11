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
    ];

    $purchasePresentation = $product->presentations
        ->first(fn ($presentation) => $presentation->purchase_enable && $presentation->active);
    $purchaseConversionFactor = (int) ($purchasePresentation?->conversion_factor ?? 0);
    $purchaseCost = $purchasePresentation?->reference_purchase_cost;
    $unitPurchasePrice = $purchasePresentation !== null && $purchaseConversionFactor > 0 && $purchaseCost !== null
        ? number_format((float) $purchaseCost / $purchaseConversionFactor, 2, '.', '')
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
            <a href="{{ route('products.edit', $product) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                </svg>
                Editar
            </a>
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
                <dd class="mt-1 text-sm text-gray-800">{{ $unitPurchasePrice ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Presentación de compra</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($purchasePresentation && $purchasePresentation->unit && $product->baseUnit)
                        {{ $purchasePresentation->unit->name }} de {{ $purchasePresentation->conversion_factor }} {{ $product->baseUnit->name }} a {{ $purchasePresentation->reference_purchase_cost }} bs
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
            @endif
        </dl>
    </div>

    {{-- Formulario de actualización de stock --}}
    @if ($purchasePresentation)
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
                            <input type="number" id="stock_packages" name="stock_packages" min="0" step="1"
                                   value="{{ old('stock_packages', intdiv($stock, $purchaseConversionFactor)) }}"
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
                        <input type="number" id="stock_loose" name="stock_loose" min="0" step="1"
                               value="{{ old('stock_loose', $purchaseConversionFactor > 1 ? $stock % $purchaseConversionFactor : $stock) }}"
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
    @endif

    {{-- Presentaciones del producto --}}
    <div class="mt-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="mb-0 text-lg font-semibold text-gray-800">Presentaciones</h2>

            <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nueva presentación
            </a>
        </div>

        @if ($product->presentations->isEmpty())
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">Este producto no tiene presentaciones</p>
                <p class="mb-0 text-sm text-gray-500">Crea la primera presentación para empezar.</p>
                <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}" class="{{ $botones['primario'] }} mt-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Nueva presentación
                </a>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Unidad</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Factor</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Precio venta</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Costo ref.</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Ganancia</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Compra</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Venta</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Código de barras</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->presentations as $presentation)
                            <tr data-href="{{ route('product-presentations.show', $presentation) }}"
                                class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-800">
                                    @if ($presentation->unit)
                                        {{ $presentation->unit->name }}
                                        <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $presentation->unit->symbol }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 tabular-nums text-gray-600">{{ $presentation->conversion_factor }}</td>
                                <td class="px-4 py-3 tabular-nums text-gray-800">{{ $presentation->sale_price ?? '—' }}</td>
                                <td class="px-4 py-3 tabular-nums text-gray-600">{{ $presentation->reference_purchase_cost ?? '—' }}</td>
                                <td class="px-4 py-3 tabular-nums text-gray-600">{{ $presentation->desired_profit }}</td>
                                <td class="px-4 py-3">
                                    @if ($presentation->purchase_enable)
                                        <span class="font-medium text-green-700">Sí</span>
                                    @else
                                        <span class="text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($presentation->sale_enable)
                                        <span class="font-medium text-green-700">Sí</span>
                                    @else
                                        <span class="text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $presentation->barcode ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($presentation->active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                                    @endif
                                </td>
                                <td data-href-ignore class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('product-presentations.show', $presentation) }}" class="{{ $botones['secundario-sm'] }}">Ver</a>
                                        <a href="{{ route('product-presentations.edit', $presentation) }}" class="{{ $botones['marino-sm'] }}">Editar</a>

                                        @if ($presentation->active)
                                            <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST"
                                                  onsubmit="return confirm('¿Dar de baja esta presentación? Podrás restaurarla después.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                            </form>
                                        @else
                                            <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST">
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
                @foreach ($product->presentations as $presentation)
                    <div data-href="{{ route('product-presentations.show', $presentation) }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="mb-0 font-semibold text-gray-800">
                                    @if ($presentation->unit)
                                        {{ $presentation->unit->name }} ({{ $presentation->unit->symbol }})
                                    @else
                                        —
                                    @endif
                                </p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Factor: <span class="font-medium text-gray-800">{{ $presentation->conversion_factor }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Precio venta: <span class="font-medium text-gray-800">{{ $presentation->sale_price ?? '—' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Costo ref.: <span class="text-gray-800">{{ $presentation->reference_purchase_cost ?? '—' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Ganancia: <span class="text-gray-800">{{ $presentation->desired_profit }}</span></p>
                                @if ($presentation->barcode)
                                    <p class="mb-0 mt-0.5 text-sm text-gray-500">Código: <span class="text-gray-800">{{ $presentation->barcode }}</span></p>
                                @endif
                            </div>

                            @if ($presentation->active)
                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                            @else
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if ($presentation->purchase_enable)
                                <span class="inline-flex items-center rounded-md bg-maxxi-navy/10 px-2 py-0.5 text-xs font-medium text-maxxi-navy">Compra</span>
                            @endif
                            @if ($presentation->sale_enable)
                                <span class="inline-flex items-center rounded-md bg-maxxi-orange/10 px-2 py-0.5 text-xs font-medium text-maxxi-orange-dark">Venta</span>
                            @endif
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('product-presentations.show', $presentation) }}" class="{{ $botones['secundario-sm'] }}">Ver</a>
                            <a href="{{ route('product-presentations.edit', $presentation) }}" class="{{ $botones['marino-sm'] }}">Editar</a>

                            @if ($presentation->active)
                                <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST"
                                      onsubmit="return confirm('¿Dar de baja esta presentación? Podrás restaurarla después.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                </form>
                            @else
                                <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST">
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
</x-layouts.sistema>
