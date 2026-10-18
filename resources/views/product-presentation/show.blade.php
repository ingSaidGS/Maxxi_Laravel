@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Formatea montos sin decimales innecesarios: 200 -> "200", 24.5 -> "24.5", 24.05 -> "24.05".
    $formatAmount = function ($value): ?string {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    };

    $baseUnitSymbol = $presentation->product?->baseUnit?->symbol;
@endphp

<x-layouts.sistema title="Presentación #{{ $presentation->id }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mb-0 text-2xl font-semibold text-gray-800">
                    {{ $presentation->unit?->name ?? 'Presentación' }}
                </h1>

                @if ($presentation->active)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                @endif
            </div>
            <p class="mb-0 mt-1 text-sm text-gray-500">
                Presentación #{{ $presentation->id }} &middot; Producto: {{ $presentation->product?->name ?? '—' }}
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('products.show', $presentation->product_id) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al producto
            </a>
            @can('manage-catalog')
                <a href="{{ route('product-presentations.edit', $presentation) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
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
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Producto</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $presentation->product?->name ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Unidad</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($presentation->unit)
                        {{ $presentation->unit->name }}
                        <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $presentation->unit->symbol }}</span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Factor de conversión</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $presentation->conversion_factor }}{{ $baseUnitSymbol ? ' '.$baseUnitSymbol : '' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Tamaño</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    {{ $presentation->presentation_size ? ucfirst($presentation->presentation_size) : '—' }}
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Precio de venta</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $presentation->sale_price !== null ? $formatAmount($presentation->sale_price).' Bs.' : '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Costo de compra de referencia</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $presentation->reference_purchase_cost !== null ? $formatAmount($presentation->reference_purchase_cost).' Bs.' : '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Ganancia deseada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $presentation->desired_profit !== null ? $formatAmount($presentation->desired_profit).' Bs.' : '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Código de barras</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $presentation->barcode ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Compra habilitada</dt>
                <dd class="mt-1">
                    @if ($presentation->purchase_enable)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Sí</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">No</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Venta habilitada</dt>
                <dd class="mt-1">
                    @if ($presentation->sale_enable)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Sí</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">No</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
                <dd class="mt-1">
                    @if ($presentation->active)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creada</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $presentation->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizada</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $presentation->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.sistema>
