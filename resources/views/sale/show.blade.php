@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        'whatsapp' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1ebe5b] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2',
    ];
@endphp

@php
    // Pluralización simple en español (misma convención que product/show.blade.php):
    // vocal final -> +s, consonante -> +es. Acepta enteros o decimales.
    $pluralize = fn (string $name, int|float $count): string => (float) $count == 1.0
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    // Totales por producto expresados en la unidad base del producto.
    $productTotals = $sale->saleDetails
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
    // presentación): sale del catálogo de presentaciones del producto (solo las
    // activas), no de las líneas vendidas. Sirve como "1 unidad" para mostrar
    // la cantidad en la unidad original.
    $refFactorByGroup = $sale->saleDetails
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

    // Importe con 1 decimal (misma convención que el ticket).
    $money = fn ($value): string => number_format((float) $value, 1, '.', '');
    $subtotals = (float) $sale->saleDetails->sum('subtotal');

    // Mensaje de WhatsApp: mismo contenido que el ticket imprimible, en texto plano.
    $whatsappLines = [
        '*Maxxi*',
        'Sistema de Ventas',
        '',
        '*TICKET DE VENTA #'.$sale->id.'*',
        'Fecha: '.($sale->sold_at?->format('d/m/Y H:i') ?? '—'),
        'Estado: '.($sale->status === 'pagada' ? 'PAGADA' : 'FIADA'),
        'Cliente: '.($sale->customer_name ?: '—'),
    ];

    if ($sale->customer_phone) {
        $whatsappLines[] = 'Teléfono: '.$sale->customer_phone;
    }

    $whatsappLines[] = 'Cajero: '.($sale->user?->name ?? '—');
    $whatsappLines[] = '';
    $whatsappLines[] = '*Detalle*';

    if ($sale->saleDetails->isEmpty()) {
        $whatsappLines[] = 'Sin detalle';
    } else {
        foreach ($sale->saleDetails as $detail) {
            $whatsappLines[] = '- '.$quantityLabel($detail)
                .' · '.($detail->presentation?->product?->name ?? '—')
                .' · '.$money($detail->unit_price).' c/u'
                .' = Bs. '.$money($detail->subtotal);
        }
    }

    $whatsappLines[] = '';
    $whatsappLines[] = 'Subtotal: Bs. '.$money($subtotals);

    if ((float) $sale->sale_discount > 0) {
        $whatsappLines[] = 'Descuento: - Bs. '.$money($sale->sale_discount);
    }

    $whatsappLines[] = 'TOTAL: Bs. '.$money($sale->total);
    $whatsappLines[] = 'Efectivo: Bs. '.$money($sale->cash);
    $whatsappLines[] = 'QR: Bs. '.$money($sale->qr);

    if ((float) $sale->debt > 0) {
        $whatsappLines[] = 'FIADO: Bs. '.$money($sale->debt);
    }

    $whatsappLines[] = '';
    $whatsappLines[] = '¡Gracias por su compra!';
    $whatsappLines[] = 'Sistema Desarrollado por Aura Tech';
    $whatsappLines[] = 'Contacto: 68749269';

    // Número en formato internacional para wa.me. En Bolivia los móviles se
    // guardan como 8 dígitos locales, así que se antepone el código de país 591.
    // Si la venta no tiene teléfono, el enlace abre WhatsApp sin destinatario
    // (permite elegir el contacto).
    $whatsappPhone = preg_replace('/\D/', '', (string) $sale->customer_phone);

    if (strlen($whatsappPhone) === 8 && ! str_starts_with($whatsappPhone, '591')) {
        $whatsappPhone = '591'.$whatsappPhone;
    }

    $whatsappUrl = 'https://wa.me/'.$whatsappPhone.'?text='.rawurlencode(implode("\n", $whatsappLines));
@endphp

<x-layouts.sistema title="Venta #{{ $sale->id }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mb-0 text-2xl font-semibold text-gray-800">Venta #{{ $sale->id }}</h1>

                @if ($sale->status === 'pagada')
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                @endif
            </div>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registrada el {{ $sale->sold_at?->format('d/m/Y H:i') }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
            <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
            <a href="{{ route('sales.edit', $sale) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                </svg>
                Editar
            </a>
            <a href="{{ route('sales.ticket', $sale) }}" target="_blank" rel="noopener" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                </svg>
                Imprimir
            </a>
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="{{ $botones['whatsapp'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                WhatsApp
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
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Cliente</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $sale->customer_name ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Teléfono</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->customer_phone ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fecha de venta</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->sold_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($sale->status === 'pagada')
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total</dt>
                <dd class="mt-1 text-sm font-semibold tabular-nums text-gray-800">{{ $sale->total }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Descuento</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->sale_discount }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Efectivo</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->cash }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">QR</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->qr }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fiado (deuda)</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->debt }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Usuario (cajero)</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $sale->user?->name ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $sale->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Detalle de la venta --}}
    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Detalle de la venta</h2>

        @if ($sale->saleDetails->isEmpty())
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z" />
                </svg>
                <p class="mb-0 mt-3 font-medium text-gray-700">Esta venta no tiene detalles registrados</p>
                <p class="mb-0 text-sm text-gray-500">Agrega productos al editar la venta.</p>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Cantidad</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Precio unitario</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->saleDetails as $detail)
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
                @foreach ($sale->saleDetails as $detail)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="mb-0 font-semibold tabular-nums text-gray-800">{{ $quantityLabel($detail) }}</p>
                        <p class="mb-0 mt-1 text-sm text-gray-500">Producto: <span class="text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</span></p>
                        <p class="mb-0 mt-0.5 text-sm text-gray-500">Precio unitario: <span class="tabular-nums text-gray-800">Bs. {{ $detail->unit_price }}</span></p>
                        <p class="mb-0 mt-2 text-sm text-gray-500">Subtotal: <span class="font-semibold tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</span></p>
                    </div>
                @endforeach
            </div>

            {{-- Resumen por producto en unidad base --}}
            <div class="mt-4 rounded-xl border border-maxxi-navy/15 bg-maxxi-navy/5 p-4">
                <h3 class="mb-2 text-sm font-semibold text-maxxi-navy">Total por producto (unidad base)</h3>
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
