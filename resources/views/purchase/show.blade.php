@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

@php
    // Pluralización simple en español (misma convención que sale/show.blade.php):
    // vocal final -> +s, consonante -> +es. Acepta enteros o decimales.
    $pluralize = fn (string $name, int|float $count): string => (float) $count == 1.0
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    // Totales por producto expresados en la unidad base del producto.
    $productTotals = $purchase->purchaseDetails
        ->groupBy(fn ($detail) => $detail->presentation?->product?->id ?? 'sin-producto')
        ->map(function ($details) {
            $product = $details->first()->presentation?->product;

            return [
                'name' => $product?->name ?? '—',
                'base_unit_name' => $product?->baseUnit?->name,
                'base_unit_symbol' => $product?->baseUnit?->symbol,
                'base_quantity' => (int) $details->sum('base_quantity'),
            ];
        })
        ->values();

    // Menor factor de conversión de referencia por (producto + unidad de
    // presentación): sale del catálogo de presentaciones activas del producto.
    $refFactorByGroup = $purchase->purchaseDetails
        ->map(fn ($detail) => $detail->presentation?->product)
        ->filter()
        ->unique('id')
        ->flatMap(fn ($product) => $product->presentations)
        ->filter(fn ($presentation) => $presentation->active)
        ->groupBy(fn ($presentation) => $presentation->product_id.'|'.$presentation->unit_id)
        ->map(fn ($presentations) => (int) $presentations->min('conversion_factor'));

    // Formato: sin decimales si el número es entero; con 1 decimal si no lo es.
    $formatQuantity = fn (int|float $value): string => floor((float) $value) == (float) $value
        ? (string) (int) $value
        : number_format((float) $value, 1, '.', '');

    // Cantidad visual de una línea, expresada en la unidad de su presentación y
    // normalizada por el menor factor del grupo (producto + unidad). Es solo
    // visual: no altera los datos guardados en la base de datos.
    $quantityLabel = function ($detail) use ($pluralize, $formatQuantity, $refFactorByGroup): string {
        $presentation = $detail->presentation;
        $unit = $presentation?->unit;

        $ref = 0;

        if ($presentation && $unit) {
            $key = ($presentation->product?->id ?? 'sin-producto').'|'.$presentation->unit_id;
            $ref = (int) ($refFactorByGroup[$key] ?? $presentation->conversion_factor);
        }

        if ($ref <= 0) {
            $ref = (int) ($presentation?->conversion_factor ?: 1);
        }

        $value = (int) $detail->base_quantity / $ref;
        $number = $formatQuantity($value);

        if ($unit?->name) {
            return $number.' '.$pluralize($unit->name, $value);
        }

        return $unit?->symbol ? $number.' '.$unit->symbol : $number;
    };
@endphp

<x-layouts.sistema title="Compra #{{ $purchase->id }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Compra #{{ $purchase->id }}</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registrada el {{ $purchase->purchased_at?->format('d/m/Y H:i') }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
            <a href="{{ route('purchases.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
            <a href="{{ route('purchases.edit', $purchase) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
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

    {{-- Ficha de detalle --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <dl class="mb-0 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Proveedor</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $purchase->supplier ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Comprobante</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $purchase->voucher_number ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fecha de compra</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $purchase->purchased_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total</dt>
                <dd class="mt-1 text-sm font-semibold tabular-nums text-maxxi-orange-dark">Bs. {{ $purchase->total }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Usuario</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $purchase->user?->name ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $purchase->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $purchase->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Detalle de la compra --}}
    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Detalle de la compra</h2>

        @if ($purchase->purchaseDetails->isEmpty())
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z" />
                </svg>
                <p class="mb-0 mt-3 font-medium text-gray-700">Esta compra no tiene detalles registrados</p>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Cantidad</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Costo unitario</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->purchaseDetails as $detail)
                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 tabular-nums text-gray-800">{{ $quantityLabel($detail) }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">Bs. {{ $detail->unit_price }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($purchase->purchaseDetails as $detail)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="mb-0 font-semibold tabular-nums text-gray-800">{{ $quantityLabel($detail) }}</p>
                        <p class="mb-0 mt-1 text-sm text-gray-500">Producto: <span class="text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</span></p>
                        <p class="mb-0 mt-0.5 text-sm text-gray-500">Costo unitario: <span class="tabular-nums text-gray-800">Bs. {{ $detail->unit_price }}</span></p>
                        <p class="mb-0 mt-2 text-sm text-gray-500">Subtotal: <span class="font-semibold tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</span></p>
                    </div>
                @endforeach
            </div>

            {{-- Total de la compra --}}
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-maxxi-orange/30 bg-maxxi-orange/5 px-4 py-3">
                <span class="text-sm font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</span>
                <span class="text-lg font-bold tabular-nums text-maxxi-orange-dark">Bs. {{ $purchase->total }}</span>
            </div>

            {{-- Resumen por producto en unidad base --}}
            <div class="mt-4 rounded-xl border border-maxxi-navy/15 bg-maxxi-navy/5 p-4">
                <h3 class="mb-2 text-sm font-semibold text-maxxi-navy">Total ingresado por producto (unidad base)</h3>
                <ul class="mb-0 list-none space-y-1 p-0">
                    @foreach ($productTotals as $row)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700">{{ $row['name'] }}</span>
                            <span class="font-semibold tabular-nums text-gray-800">{{ $row['base_quantity'] }} {{ $row['base_unit_name'] ? $pluralize($row['base_unit_name'], $row['base_quantity']) : $row['base_unit_symbol'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layouts.sistema>
