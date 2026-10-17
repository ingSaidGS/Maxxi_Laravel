@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        'entregar' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
        'whatsapp' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1ebe5b] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2',
    ];

    // Estado de la nota: etiqueta + clases del badge (maxxi-ui §Badges).
    $statusMeta = [
        'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-blue-100 text-blue-700'],
        'entregada' => ['label' => 'Entregada', 'class' => 'bg-green-100 text-green-700'],
        'anulada' => ['label' => 'Anulada', 'class' => 'bg-gray-200 text-gray-600'],
    ];
    $meta = $statusMeta[$saleNote->status] ?? ['label' => ucfirst($saleNote->status), 'class' => 'bg-gray-100 text-gray-600'];

    // Pluralización simple en español (misma convención que sale/show.blade.php):
    // vocal final -> +s, consonante -> +es. Acepta enteros o decimales.
    $pluralize = fn (string $name, int|float $count): string => (float) $count == 1.0
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    $activeDetails = $saleNote->saleNoteDetails->where('active', true);
    $inactiveDetails = $saleNote->saleNoteDetails->where('active', false);

    // Totales por producto expresados en la unidad base (solo líneas activas:
    // son las que cuentan para el total y para la venta generada).
    $productTotals = $activeDetails
        ->groupBy(fn ($detail) => $detail->presentation?->product?->id ?? 'sin-producto')
        ->map(function ($details) {
            $product = $details->first()->presentation?->product;

            return [
                'id' => $product?->id ?? 'sin-producto',
                'name' => $product?->name ?? '—',
                'base_unit_name' => $product?->baseUnit?->name,
                'base_unit_symbol' => $product?->baseUnit?->symbol,
                'base_quantity' => (int) $details->sum('base_quantity'),
            ];
        })
        ->values();

    // Menor factor de conversión de referencia por (producto + unidad de
    // presentación): igual que en sale/show.blade.php. Sirve como "1 unidad"
    // para mostrar la cantidad en la unidad original.
    $refFactorByGroup = $saleNote->saleNoteDetails
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

    // Menor factor de conversión de referencia de una línea concreta (expuesto
    // también como data-* para el JS del modal de cantidad).
    $detailRef = function ($detail) use ($refFactorByGroup): int {
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

        return $ref;
    };

    // Cantidad visual de una línea, expresada en la unidad de su presentación.
    $quantityLabel = function ($detail) use ($pluralize, $formatQuantity, $detailRef): string {
        $presentation = $detail->presentation;
        $unit = $presentation?->unit;

        $ref = $detailRef($detail);

        $value = (int) $detail->base_quantity / $ref;
        $number = $formatQuantity($value);

        if ($unit?->name) {
            return $number.' '.$pluralize($unit->name, $value);
        }

        return $unit?->symbol ? $number.' '.$unit->symbol : $number;
    };

    // Importe con 1 decimal (misma convención que el ticket).
    $money = fn ($value): string => number_format((float) $value, 1, '.', '');
    $activeSubtotals = (float) $activeDetails->sum('subtotal');
    $total = (float) $saleNote->total;

    // Enlace al mapa si la nota tiene coordenadas.
    $hasLocation = $saleNote->delivery_latitude !== null && $saleNote->delivery_longitude !== null;
    $mapUrl = $hasLocation
        ? 'https://www.google.com/maps?q='.$saleNote->delivery_latitude.','.$saleNote->delivery_longitude
        : null;

    // Mensaje de WhatsApp: resumen del pedido en texto plano.
    $whatsappLines = [
        '*Maxxi*',
        'Sistema de Ventas',
        '',
        '*NOTA DE VENTA #'.$saleNote->id.'*',
        'Fecha: '.($saleNote->noted_at?->format('d/m/Y H:i') ?? '—'),
        'Estado: '.mb_strtoupper($meta['label']),
        'Cliente: '.($saleNote->customer_name ?: '—'),
    ];

    if ($saleNote->customer_phone) {
        $whatsappLines[] = 'Teléfono: '.$saleNote->customer_phone;
    }

    if ($saleNote->scheduled_for) {
        $whatsappLines[] = 'Entrega programada: '.$saleNote->scheduled_for->format('d/m/Y H:i');
    }

    $whatsappLines[] = 'Atendido por: '.($saleNote->user?->name ?? '—');
    $whatsappLines[] = '';
    $whatsappLines[] = '*Detalle*';

    if ($activeDetails->isEmpty()) {
        $whatsappLines[] = 'Sin líneas activas';
    } else {
        foreach ($activeDetails as $detail) {
            $whatsappLines[] = '- '.$quantityLabel($detail)
                .' · '.($detail->presentation?->product?->name ?? '—')
                .' · '.$money($detail->unit_price).' c/u'
                .' = Bs. '.$money($detail->subtotal);
        }
    }

    $whatsappLines[] = '';
    $whatsappLines[] = 'Subtotal: Bs. '.$money($activeSubtotals);

    if ((float) $saleNote->sale_discount > 0) {
        $whatsappLines[] = 'Descuento: - Bs. '.$money($saleNote->sale_discount);
    }

    $whatsappLines[] = 'TOTAL: Bs. '.$money($total);
    $whatsappLines[] = '';
    $whatsappLines[] = '¡Gracias por su preferencia!';
    $whatsappLines[] = 'Sistema Desarrollado por Aura Tech';
    $whatsappLines[] = 'Contacto: 68749269';

    // Número en formato internacional para wa.me (Bolivia: se antepone 591 a los
    // móviles de 8 dígitos). Sin teléfono, abre WhatsApp sin destinatario.
    $whatsappPhone = preg_replace('/\D/', '', (string) $saleNote->customer_phone);

    if (strlen($whatsappPhone) === 8 && ! str_starts_with($whatsappPhone, '591')) {
        $whatsappPhone = '591'.$whatsappPhone;
    }

    $whatsappUrl = 'https://wa.me/'.$whatsappPhone.'?text='.rawurlencode(implode("\n", $whatsappLines));

    // Datos de las líneas para el modal de cantidad. El JS usa la vista previa y,
    // al guardar, envía la nueva cantidad a la ruta "quantity_url" de cada línea.
    $detailsForJs = $saleNote->saleNoteDetails->map(function ($detail) use ($detailRef, $saleNote) {
        $presentation = $detail->presentation;
        $product = $presentation?->product;

        return [
            'id' => $detail->id,
            'active' => (bool) $detail->active,
            'quantity' => (int) $detail->quantity,
            'conversion_factor' => (int) $detail->conversion_factor,
            'ref' => $detailRef($detail),
            'unit_price' => (float) $detail->unit_price,
            'presentation_id' => $presentation?->id,
            'product_id' => $product?->id ?? 'sin-producto',
            'product_name' => $product?->name ?? '—',
            'unit_name' => $presentation?->unit?->name,
            'unit_symbol' => $presentation?->unit?->symbol,
            'base_unit_name' => $product?->baseUnit?->name,
            'base_unit_symbol' => $product?->baseUnit?->symbol,
            'quantity_url' => route('sale-notes.details.quantity', [$saleNote, $detail]),
        ];
    })->values();

    // Presentaciones vendibles para el modal "Agregar producto" (front). El
    // método show() del controller todavía NO las envía (llegan como
    // $presentations, igual que en create). Se usa "??" para no romper el
    // render mientras tanto: si no llegan, el modal avisa que faltan.
    $saleablePresentations = collect($presentations ?? []);
@endphp

<x-layouts.sistema title="Nota de venta #{{ $saleNote->id }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mb-0 text-2xl font-semibold text-gray-800">Nota de venta #{{ $saleNote->id }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
            </div>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registrada el {{ $saleNote->noted_at?->format('d/m/Y H:i') }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
            <a href="{{ route('sale-notes.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>

            @if ($saleNote->status === 'pendiente')
                <a href="{{ route('sale-notes.edit', $saleNote) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                    </svg>
                    Editar
                </a>
                <button type="button" id="open-deliver" class="{{ $botones['entregar'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H18.75m-2.25 3.75H14.25m4.5-3.75V6.75A2.25 2.25 0 0016.5 4.5h-6A2.25 2.25 0 008.25 6.75v4.5m0 0H3.375A1.125 1.125 0 002.25 12.375v1.5c0 .621.504 1.125 1.125 1.125h1.5" />
                    </svg>
                    Entregar
                </button>
                <button type="button" id="open-cancel" class="{{ $botones['peligro'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Anular
                </button>
            @elseif ($saleNote->status === 'entregada' && $saleNote->sale)
                <a href="{{ route('sales.show', $saleNote->sale) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Ver venta #{{ $saleNote->sale->id }}
                </a>
            @endif

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

    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
            </svg>
            <div>
                <p class="mb-1 font-semibold">No se pudo completar la operación:</p>
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
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Cliente</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $saleNote->customer_name ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Teléfono</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->customer_phone ?: '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fecha de la nota</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->noted_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Entrega programada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->scheduled_for?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total</dt>
                <dd data-ficha-total class="mt-1 text-sm font-semibold tabular-nums text-maxxi-orange-dark">Bs. {{ $money($total) }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Descuento</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">Bs. {{ $money($saleNote->sale_discount) }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Registrada por</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $saleNote->user?->name ?? '—' }}</dd>
            </div>

            @if ($saleNote->status === 'entregada')
                <div>
                    <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Entregada el</dt>
                    <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->delivered_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>

                @if ($saleNote->sale)
                    <div>
                        <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Venta generada</dt>
                        <dd class="mt-1 text-sm text-gray-800">
                            <a href="{{ route('sales.show', $saleNote->sale) }}" class="font-medium text-maxxi-orange hover:text-maxxi-orange-dark hover:underline">Venta #{{ $saleNote->sale->id }}</a>
                        </dd>
                    </div>
                @endif
            @endif

            <div class="{{ $hasLocation ? '' : 'sm:col-span-2 lg:col-span-3' }}">
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Ubicación de entrega</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($hasLocation)
                        <span class="tabular-nums">{{ $saleNote->delivery_latitude }}, {{ $saleNote->delivery_longitude }}</span>
                        <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="ml-2 inline-flex items-center gap-1 font-medium text-maxxi-orange hover:text-maxxi-orange-dark hover:underline">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Ver en mapa
                        </a>
                    @else
                        —
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizada</dt>
                <dd class="mt-1 text-sm tabular-nums text-gray-800">{{ $saleNote->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Detalle de la nota --}}
    <div class="mt-8">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <h2 class="mb-0 text-lg font-semibold text-gray-800">Detalle de la nota</h2>
            @if ($saleNote->status === 'pendiente')
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">Puedes agregar, quitar o reactivar productos</span>
                <button type="button" data-open-add-detail class="{{ $botones['primario'] }} ml-auto w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Agregar producto
                </button>
            @endif
        </div>

        @if ($saleNote->saleNoteDetails->isEmpty())
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z" />
                </svg>
                <p class="mb-0 mt-3 font-medium text-gray-700">Esta nota no tiene detalles registrados</p>
                @if ($saleNote->status === 'pendiente')
                    <button type="button" data-open-add-detail class="{{ $botones['primario'] }} mt-4">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Agregar producto
                    </button>
                @endif
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
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            @if ($saleNote->status === 'pendiente')
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($saleNote->saleNoteDetails as $detail)
                            <tr data-detail-id="{{ $detail->id }}" class="border-t border-gray-100 transition {{ $detail->active ? 'hover:bg-maxxi-cream/60' : 'bg-gray-50/60 text-gray-400' }}">
                                <td data-detail-quantity class="px-4 py-3 tabular-nums {{ $detail->active ? 'text-gray-800' : 'text-gray-400' }}">{{ $quantityLabel($detail) }}</td>
                                <td class="px-4 py-3 font-medium {{ $detail->active ? 'text-gray-800' : 'text-gray-400' }}">{{ $detail->presentation?->product?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums {{ $detail->active ? 'text-gray-600' : 'text-gray-400' }}">Bs. {{ $detail->unit_price }}</td>
                                <td data-detail-subtotal class="px-4 py-3 text-right font-medium tabular-nums {{ $detail->active ? 'text-gray-800' : 'text-gray-400 line-through' }}">Bs. {{ $detail->subtotal }}</td>
                                <td class="px-4 py-3">
                                    @if ($detail->active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-600">Quitada</span>
                                    @endif
                                </td>
                                @if ($saleNote->status === 'pendiente')
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            @if ($detail->active)
                                                <button type="button" data-quantity-edit="{{ $detail->id }}"
                                                        class="{{ $botones['marino'] }} px-2.5 py-1.5 text-xs">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h16M4.5 12h16M4.5 18h10" />
                                                    </svg>
                                                    Cantidad
                                                </button>
                                                <form method="POST" action="{{ route('sale-notes.details.deactivate', [$saleNote, $detail]) }}"
                                                      data-detail-form data-detail-action="quitar" data-detail-label="{{ $detail->presentation?->product?->name ?? '' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="{{ $botones['peligro'] }} px-2.5 py-1.5 text-xs">Quitar</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('sale-notes.details.restore', [$saleNote, $detail]) }}"
                                                      data-detail-form data-detail-action="reactivar" data-detail-label="{{ $detail->presentation?->product?->name ?? '' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="{{ $botones['exito'] }} px-2.5 py-1.5 text-xs">Reactivar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($saleNote->saleNoteDetails as $detail)
                    <div data-detail-id="{{ $detail->id }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm {{ $detail->active ? '' : 'opacity-60' }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="mb-0 font-semibold text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</p>
                            @if ($detail->active)
                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                            @else
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-600">Quitada</span>
                            @endif
                        </div>
                        <p class="mb-0 mt-1 text-sm tabular-nums text-gray-500">Cantidad: <span data-detail-quantity class="text-gray-800">{{ $quantityLabel($detail) }}</span></p>
                        <p class="mb-0 mt-0.5 text-sm text-gray-500">Precio unitario: <span class="tabular-nums text-gray-800">Bs. {{ $detail->unit_price }}</span></p>
                        <p class="mb-0 mt-2 text-sm text-gray-500">Subtotal: <span data-detail-subtotal class="font-semibold tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</span></p>

                        @if ($saleNote->status === 'pendiente')
                            <div class="mt-3 flex justify-end gap-2">
                                @if ($detail->active)
                                    <button type="button" data-quantity-edit="{{ $detail->id }}"
                                            class="{{ $botones['marino'] }} px-2.5 py-1.5 text-xs">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h16M4.5 12h16M4.5 18h10" />
                                        </svg>
                                        Cantidad
                                    </button>
                                    <form method="POST" action="{{ route('sale-notes.details.deactivate', [$saleNote, $detail]) }}"
                                          data-detail-form data-detail-action="quitar" data-detail-label="{{ $detail->presentation?->product?->name ?? '' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="{{ $botones['peligro'] }} px-2.5 py-1.5 text-xs">Quitar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('sale-notes.details.restore', [$saleNote, $detail]) }}"
                                          data-detail-form data-detail-action="reactivar" data-detail-label="{{ $detail->presentation?->product?->name ?? '' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="{{ $botones['exito'] }} px-2.5 py-1.5 text-xs">Reactivar</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Resumen de importes --}}
    @if ($saleNote->saleNoteDetails->isNotEmpty())
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Totales por producto en unidad base --}}
            @if ($productTotals->isNotEmpty())
                <div class="rounded-xl border border-maxxi-navy/15 bg-maxxi-navy/5 p-4">
                    <h3 class="mb-2 text-sm font-semibold text-maxxi-navy">Total por producto (unidad base)</h3>
                    <ul class="mb-0 list-none space-y-1 p-0">
                        @foreach ($productTotals as $row)
                            <li data-product-total="{{ $row['id'] }}" class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-gray-700">{{ $row['name'] }}</span>
                                <span data-product-total-value class="font-semibold tabular-nums text-gray-800">{{ $row['base_quantity'] }} {{ $row['base_unit_name'] ? $pluralize($row['base_unit_name'], $row['base_quantity']) : $row['base_unit_symbol'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mb-0 mt-2 text-xs text-gray-500">Calculado solo con las líneas activas.</p>
                </div>
            @endif

            {{-- Importes --}}
            <div class="rounded-xl border border-maxxi-orange/30 bg-white p-4 shadow-sm">
                <dl class="mb-0 space-y-2">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <dt class="text-gray-500">Subtotal (líneas activas)</dt>
                        <dd data-summary-subtotal class="mb-0 font-medium tabular-nums text-gray-800">Bs. {{ $money($activeSubtotals) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <dt class="text-gray-500">Descuento</dt>
                        <dd class="mb-0 font-medium tabular-nums text-gray-800">- Bs. {{ $money($saleNote->sale_discount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2">
                        <dt class="text-sm font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</dt>
                        <dd data-summary-total class="mb-0 text-lg font-bold tabular-nums text-maxxi-orange-dark">Bs. {{ $money($total) }}</dd>
                    </div>
                </dl>

                @if ($inactiveDetails->isNotEmpty())
                    <p class="mb-0 mt-3 text-xs text-gray-500">
                        {{ $inactiveDetails->count() }} {{ $inactiveDetails->count() === 1 ? 'línea quitada' : 'líneas quitadas' }} no {{ $inactiveDetails->count() === 1 ? 'cuenta' : 'cuentan' }} en el total.
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Modal: entregar la nota (captura de pago) --}}
    @if ($saleNote->status === 'pendiente')
        <x-modal name="deliver-sale-note" max-width="lg" focusable>
            <form id="deliver-form" method="POST" action="{{ route('sale-notes.entregar', $saleNote) }}">
                @csrf

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H18.75m-2.25 3.75H14.25m4.5-3.75V6.75A2.25 2.25 0 0016.5 4.5h-6A2.25 2.25 0 008.25 6.75v4.5m0 0H3.375A1.125 1.125 0 002.25 12.375v1.5c0 .621.504 1.125 1.125 1.125h1.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="mb-1 text-lg font-semibold text-gray-800">Entregar nota #{{ $saleNote->id }}</h2>
                            <p class="mb-0 text-sm text-gray-600">Registra el pago recibido. Se descontará el stock y se generará la venta.</p>
                        </div>
                    </div>

                    @if ($activeDetails->isEmpty())
                        <div role="alert" class="mt-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                            </svg>
                            <span>La nota no tiene líneas activas para entregar. Reactiva al menos un producto.</span>
                        </div>
                    @endif

                    {{-- Resumen del total --}}
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                        <span class="text-sm font-medium text-gray-600">Total a pagar</span>
                        <span data-deliver-total class="text-lg font-bold tabular-nums text-maxxi-orange-dark">Bs. {{ $money($total) }}</span>
                    </div>

                    {{-- Pago --}}
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="received_cash" class="mb-1 block text-sm font-medium text-gray-700">Efectivo (Bs.)</label>
                            <input type="number" id="received_cash" name="received_cash" value="" step="0.1" min="0" inputmode="decimal"
                                   placeholder="Bs. 0.0"
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring focus:ring-maxxi-orange/40" />
                        </div>

                        <div>
                            <label for="received_qr" class="mb-1 block text-sm font-medium text-gray-700">QR (Bs.)</label>
                            <input type="number" id="received_qr" name="received_qr" value="" step="0.1" min="0" inputmode="decimal"
                                   placeholder="Bs. 0.0"
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring focus:ring-maxxi-orange/40" />
                        </div>
                    </div>

                    {{-- Vista previa del estado derivado --}}
                    <div class="mt-4 rounded-lg border border-gray-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-500">Estado de la venta</span>
                            <span id="deliver-status" class="font-semibold text-green-700">Pagada</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-500">Deuda (fiado)</span>
                            <span id="deliver-debt" class="font-semibold tabular-nums text-gray-800">Bs. 0.0</span>
                        </div>
                    </div>

                    <p id="deliver-customer-warning" class="mt-3 mb-0 hidden text-sm text-red-600">
                        Si la venta queda fiada, el cliente debe tener nombre y teléfono registrados. Edita la nota para completarlos.
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="{{ $botones['secundario'] }}" x-on:click="show = false">Cancelar</button>
                    <button type="submit" id="deliver-submit" class="{{ $botones['entregar'] }}" @disabled($activeDetails->isEmpty())>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Entregar y generar venta
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: confirmar anulación --}}
        <x-modal name="cancel-sale-note" max-width="md" focusable>
            <form method="POST" action="{{ route('sale-notes.destroy', $saleNote) }}">
                @csrf
                @method('DELETE')

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Anular la nota #{{ $saleNote->id }}?</h2>
                            <p class="mb-0 text-sm text-gray-600">La nota quedará como <span class="font-medium">anulada</span> y no podrá entregarse.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="{{ $botones['secundario'] }}" x-on:click="show = false">Cancelar</button>
                    <button type="submit" class="{{ $botones['peligro'] }}">Sí, anular</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: confirmar quitar/reactivar una línea --}}
        <x-modal name="confirm-detail-toggle" max-width="md" focusable>
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <span id="detail-toggle-icon" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                        </svg>
                    </span>
                    <div>
                        <h2 id="detail-toggle-title" class="mb-1 text-lg font-semibold text-gray-800">¿Quitar el producto?</h2>
                        <p id="detail-toggle-summary" class="mb-0 text-sm text-gray-600"></p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="{{ $botones['secundario'] }}" x-on:click="show = false">Cancelar</button>
                    <button type="button" id="detail-toggle-confirm" class="{{ $botones['peligro'] }}">Sí, quitar</button>
                </div>
            </div>
        </x-modal>
        {{-- Modal: modificar la cantidad de una línea (solo notas pendientes) --}}
        <x-modal name="edit-detail-quantity" max-width="md" focusable>
            <form id="quantity-form" method="POST" action="">
                @csrf
                @method('PATCH')

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h16M4.5 12h16M4.5 18h10" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="mb-1 text-lg font-semibold text-gray-800">Modificar cantidad</h2>
                            <p id="quantity-summary" class="mb-0 text-sm text-gray-600"></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="quantity-input" class="mb-1 block text-sm font-medium text-gray-700">Nueva cantidad</label>
                        <input type="number" id="quantity-input" name="quantity" min="1" step="1" inputmode="numeric" value="1"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring focus:ring-maxxi-orange/40" />
                        <p id="quantity-input-error" class="mt-1 mb-0 hidden text-sm text-red-600">Ingresa una cantidad entera mayor o igual a 1.</p>
                    </div>

                    {{-- Vista previa de la equivalencia y del nuevo subtotal --}}
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-500">Equivale a</span>
                            <span id="quantity-base-preview" class="font-medium tabular-nums text-gray-800">—</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-500">Nuevo subtotal</span>
                            <span id="quantity-subtotal-preview" class="font-semibold tabular-nums text-maxxi-orange-dark">Bs. 0.0</span>
                        </div>
                    </div>

                    <p class="mb-0 mt-3 text-xs text-gray-500">El cambio se guarda al confirmar y el total de la nota se recalcula en el servidor.</p>

                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" class="{{ $botones['secundario'] }}" x-on:click="show = false">Cancelar</button>
                        <button type="submit" class="{{ $botones['primario'] }}">Guardar cantidad</button>
                    </div>
                </div>
            </form>
        </x-modal>

        {{-- Modal: agregar una presentación a la nota (solo pendientes).
             Envía presentation_id + quantity; el backend deriva precios/costos,
             suma a la línea existente si ya está y recalcula el total. --}}
        <x-modal name="add-sale-note-detail" max-width="lg" focusable>
            <form id="add-detail-form" method="POST" action="{{ route('sale-notes.details.store', $saleNote) }}">
                @csrf
                <input type="hidden" name="presentation_id" id="add-presentation-id" value="">

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="mb-1 text-lg font-semibold text-gray-800">Agregar producto</h2>
                            <p class="mb-0 text-sm text-gray-600">Añade una presentación a la nota #{{ $saleNote->id }}.</p>
                        </div>
                    </div>

                    @if ($saleablePresentations->isEmpty())
                        <div role="alert" class="mt-5 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                            </svg>
                            <span>No hay presentaciones con venta habilitada para agregar a esta nota.</span>
                        </div>
                    @else
                        <div class="mt-5">
                            <label for="add-presentation-search" class="mb-1 block text-sm font-medium text-gray-700">Producto</label>
                            <div id="add-presentation-search-wrap" class="relative">
                                <input type="text" id="add-presentation-search" autocomplete="off" spellcheck="false"
                                       role="combobox" aria-expanded="false" aria-controls="add-presentation-results" aria-autocomplete="list"
                                       placeholder="Escribe el nombre o escanea el código de barras…"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                            </div>
                            {{-- Resultados en línea: el panel del modal recorta lo absoluto,
                                 por eso la lista empuja el contenido en vez de superponerse. --}}
                            <ul id="add-presentation-results" role="listbox"
                                class="mt-1 hidden max-h-64 list-none overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 pl-0 shadow-lg"></ul>
                        </div>

                        {{-- Presentación seleccionada --}}
                        <div id="add-selection-banner" class="mt-3 hidden items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2 2 4-4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="min-w-0">
                                <p id="add-selection-name" class="mb-0 truncate font-medium"></p>
                                <p id="add-selection-meta" class="mb-0 mt-0.5 text-xs text-green-700"></p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="add-presentation-qty" class="mb-1 block text-sm font-medium text-gray-700">Cantidad</label>
                                <input type="number" id="add-presentation-qty" name="quantity" value="1" min="1" step="1" inputmode="numeric"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring focus:ring-maxxi-orange/40" />
                            </div>

                            {{-- Vista previa de la equivalencia y del subtotal --}}
                            <div class="rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-500">Equivale a</span>
                                    <span id="add-equivalent" class="font-medium tabular-nums text-gray-800">—</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-3">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span id="add-subtotal" class="font-semibold tabular-nums text-maxxi-orange-dark">Bs. 0.0</span>
                                </div>
                            </div>
                        </div>

                        <p class="mb-0 mt-3 text-xs text-gray-500">Si la presentación ya está en la nota, se sumará a la línea existente. El total se recalcula en el servidor.</p>
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="{{ $botones['secundario'] }}" x-on:click="show = false">Cancelar</button>
                    <button type="submit" id="add-detail-submit" class="{{ $botones['primario'] }}" @disabled($saleablePresentations->isEmpty())>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Agregar
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    <script>
        (function () {
            function openModal(name) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
            }

            function closeModal(name) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
            }

            function parseMoney(value) {
                var s = (value === undefined || value === null) ? '' : String(value).trim();
                if (s === '') { return 0; }
                s = s.replace(',', '.');
                var num = parseFloat(s);
                return isNaN(num) ? 0 : num;
            }

            function money(value) {
                return 'Bs. ' + (Math.round(value * 10) / 10).toFixed(1);
            }

            // Botones que abren los modales de entrega y anulación.
            var openDeliver = document.getElementById('open-deliver');
            var openCancel = document.getElementById('open-cancel');

            if (openDeliver) {
                openDeliver.addEventListener('click', function () { openModal('deliver-sale-note'); });
            }

            if (openCancel) {
                openCancel.addEventListener('click', function () { openModal('cancel-sale-note'); });
            }

            // Vista previa del estado/deuda al escribir el pago.
            var total = {{ (float) $total }};
            var hasCustomer = {{ ($saleNote->customer_name && $saleNote->customer_phone) ? 'true' : 'false' }};
            var cashInput = document.getElementById('received_cash');
            var qrInput = document.getElementById('received_qr');
            var statusText = document.getElementById('deliver-status');
            var debtText = document.getElementById('deliver-debt');
            var customerWarning = document.getElementById('deliver-customer-warning');

            function refreshPreview() {
                var cash = parseMoney(cashInput ? cashInput.value : 0);
                var qr = parseMoney(qrInput ? qrInput.value : 0);
                var paid = Math.round((cash + qr) * 10) / 10;
                var fiada = paid < total - 0.05;

                if (statusText) {
                    statusText.textContent = fiada ? 'Fiada' : 'Pagada';
                    statusText.className = 'font-semibold ' + (fiada ? 'text-amber-700' : 'text-green-700');
                }

                if (debtText) {
                    debtText.textContent = fiada ? money(Math.max(0, total - paid)) : money(0);
                }

                if (customerWarning) {
                    customerWarning.classList.toggle('hidden', !(fiada && !hasCustomer));
                }
            }

            cashInput?.addEventListener('input', refreshPreview);
            qrInput?.addEventListener('input', refreshPreview);

            // Quitar/reactivar línea: intercepta el submit y lo confirma en un modal.
            var pendingDetailForm = null;
            var detailTitle = document.getElementById('detail-toggle-title');
            var detailSummary = document.getElementById('detail-toggle-summary');
            var detailConfirm = document.getElementById('detail-toggle-confirm');
            var dangerClass = @json($botones['peligro']);
            var successClass = @json($botones['exito']);

            document.querySelectorAll('[data-detail-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    pendingDetailForm = form;

                    var action = form.dataset.detailAction;
                    var label = form.dataset.detailLabel || 'este producto';
                    var isRemove = action === 'quitar';

                    if (detailTitle) {
                        detailTitle.textContent = isRemove ? '¿Quitar el producto?' : '¿Reactivar el producto?';
                    }

                    if (detailSummary) {
                        detailSummary.textContent = isRemove
                            ? 'Se quitará "' + label + '" de la nota y el total se recalculará.'
                            : 'Se reactivará "' + label + '" en la nota y el total se recalculará.';
                    }

                    if (detailConfirm) {
                        detailConfirm.textContent = isRemove ? 'Sí, quitar' : 'Sí, reactivar';
                        detailConfirm.className = isRemove ? dangerClass : successClass;
                    }

                    openModal('confirm-detail-toggle');
                });
            });

            detailConfirm?.addEventListener('click', function () {
                closeModal('confirm-detail-toggle');

                if (pendingDetailForm) {
                    var form = pendingDetailForm;
                    pendingDetailForm = null;
                    form.submit();
                }
            });

            // Modificar cantidad de una línea. El modal envía la nueva cantidad al
            // servidor (ruta "quantity_url" de cada línea) y la nota se re-renderiza
            // al recargar. Solo las notas pendientes pueden modificarse.
            var detailsData = @json($detailsForJs);
            var detailsById = {};

            detailsData.forEach(function (d) {
                detailsById[String(d.id)] = d;
            });

            var quantityModalId = null;
            var quantityForm = document.getElementById('quantity-form');
            var quantityInput = document.getElementById('quantity-input');
            var quantitySummary = document.getElementById('quantity-summary');
            var quantityBasePreview = document.getElementById('quantity-base-preview');
            var quantitySubtotalPreview = document.getElementById('quantity-subtotal-preview');
            var quantityInputError = document.getElementById('quantity-input-error');

            // Pluralización simple en español (vocal final -> +s, consonante -> +es).
            function pluralize(name, count) {
                if (Number(count) === 1) { return name; }

                return /[aeiou]$/i.test(name) ? name + 's' : name + 'es';
            }

            // Etiqueta de la unidad de la presentación (para el resumen del modal).
            function detailUnitLabel(d) {
                return d.unit_name || d.unit_symbol || 'unidad';
            }

            function detailBaseLabel(d, baseQuantity) {
                var unit = d.base_unit_name
                    ? pluralize(d.base_unit_name, baseQuantity)
                    : (d.base_unit_symbol || '');

                return unit ? baseQuantity + ' ' + unit : String(baseQuantity);
            }

            // Vista previa dentro del modal (equivalencia y nuevo subtotal).
            function refreshQuantityPreview() {
                var d = quantityModalId ? detailsById[quantityModalId] : null;
                if (!d || !quantityInput) { return; }

                var raw = quantityInput.value;
                var value = Number(raw);
                var valid = raw !== '' && Number.isInteger(value) && value >= 1;

                if (quantityBasePreview) {
                    quantityBasePreview.textContent = valid
                        ? detailBaseLabel(d, d.conversion_factor * value)
                        : '—';
                }

                if (quantitySubtotalPreview) {
                    quantitySubtotalPreview.textContent = valid ? money(value * d.unit_price) : '—';
                }
            }

            document.querySelectorAll('[data-quantity-edit]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var d = detailsById[button.dataset.quantityEdit];
                    if (!d) { return; }

                    quantityModalId = String(d.id);

                    if (quantityForm) {
                        quantityForm.action = d.quantity_url;
                    }

                    if (quantitySummary) {
                        quantitySummary.textContent = 'Producto: ' + d.product_name
                            + ' · Presentación: ' + detailUnitLabel(d)
                            + ' · Precio: ' + money(d.unit_price);
                    }

                    if (quantityInput) {
                        quantityInput.value = d.quantity;
                    }

                    quantityInputError?.classList.add('hidden');
                    refreshQuantityPreview();
                    openModal('edit-detail-quantity');
                });
            });

            quantityInput?.addEventListener('input', function () {
                quantityInputError?.classList.add('hidden');
                refreshQuantityPreview();
            });

            // Valida antes de enviar; cubre el clic en "Guardar" y el Enter del input.
            quantityForm?.addEventListener('submit', function (event) {
                var value = quantityInput ? Number(quantityInput.value) : NaN;
                var valid = !!quantityInput
                    && quantityInput.value !== ''
                    && Number.isInteger(value)
                    && value >= 1;

                if (!valid) {
                    event.preventDefault();
                    quantityInputError?.classList.remove('hidden');
                }
            });

            // ─────────────────────────────────────────────────────────────
            // Modal "Agregar producto" (front-only, acción stub por ahora).
            // Buscador de presentaciones vendibles + cantidad, con vista previa.
            // ─────────────────────────────────────────────────────────────
            var addPresentations = @json($saleablePresentations);
            var addById = {};
            addPresentations.forEach(function (p) { addById[p.id] = p; });

            // Presentaciones ya activas en la nota: si se vuelven a elegir, el
            // backend las sumará a la línea existente (se avisa en el banner).
            var activePresentationIds = {};
            detailsData.forEach(function (d) {
                if (d.active && d.presentation_id !== null && d.presentation_id !== undefined) {
                    activePresentationIds[String(d.presentation_id)] = true;
                }
            });

            var addForm = document.getElementById('add-detail-form');
            var addSearch = document.getElementById('add-presentation-search');
            var addResults = document.getElementById('add-presentation-results');
            var addHiddenId = document.getElementById('add-presentation-id');
            var addQty = document.getElementById('add-presentation-qty');
            var addBanner = document.getElementById('add-selection-banner');
            var addBannerName = document.getElementById('add-selection-name');
            var addBannerMeta = document.getElementById('add-selection-meta');
            var addEquivalent = document.getElementById('add-equivalent');
            var addSubtotalEl = document.getElementById('add-subtotal');
            var addSubmit = document.getElementById('add-detail-submit');

            var addSelected = null;
            var addMatches = [];
            var addCursor = -1;

            function closeAddResults() {
                if (!addResults) { return; }
                addResults.classList.add('hidden');
                addResults.innerHTML = '';
                if (addSearch) { addSearch.setAttribute('aria-expanded', 'false'); }
                addMatches = [];
                addCursor = -1;
            }

            function addMatchesFor(query) {
                var q = String(query || '').trim().toLowerCase();
                if (q === '') { return []; }

                return addPresentations.filter(function (p) {
                    return (p.label || '').toLowerCase().indexOf(q) !== -1
                        || (p.barcode || '').toLowerCase() === q;
                }).slice(0, 50);
            }

            function highlightAdd(index) {
                if (!addResults) { return; }
                var items = addResults.querySelectorAll('[role="option"]');

                for (var i = 0; i < items.length; i++) {
                    var on = i === index;
                    items[i].classList.toggle('bg-maxxi-cream', on);
                    items[i].setAttribute('aria-selected', on ? 'true' : 'false');
                }

                if (items[index]) { items[index].scrollIntoView({ block: 'nearest' }); }
                addCursor = index;
            }

            function renderAddResults(query) {
                if (!addResults) { return; }

                addMatches = addMatchesFor(query);
                addResults.innerHTML = '';

                if (addMatches.length === 0) {
                    closeAddResults();
                    return;
                }

                addMatches.forEach(function (p) {
                    var li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', 'false');
                    li.className = 'cursor-pointer px-3 py-2 text-sm text-gray-700 transition hover:bg-maxxi-cream';
                    li.textContent = p.label;
                    li.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        selectAddPresentation(p.id);
                    });
                    addResults.appendChild(li);
                });

                addResults.classList.remove('hidden');
                if (addSearch) { addSearch.setAttribute('aria-expanded', 'true'); }
                addCursor = -1;

                // Código de barras exacto: selecciona automáticamente.
                var q = String(query || '').trim().toLowerCase();
                var exact = addMatches.filter(function (p) {
                    return p.barcode && String(p.barcode).toLowerCase() === q;
                })[0];

                if (exact) { selectAddPresentation(exact.id); }
            }

            function selectAddPresentation(id) {
                var p = addById[id];
                if (!p) { return; }

                addSelected = p;
                if (addHiddenId) { addHiddenId.value = p.id; }
                if (addSearch) { addSearch.value = p.label; }
                closeAddResults();
                updateAddBanner();
                refreshAddPreview();
            }

            function updateAddBanner() {
                if (!addBanner) { return; }

                if (!addSelected) {
                    addBanner.classList.add('hidden');
                    addBanner.classList.remove('flex');
                    return;
                }

                if (addBannerName) { addBannerName.textContent = addSelected.label; }

                if (addBannerMeta) {
                    var extra = activePresentationIds[String(addSelected.id)]
                        ? ' · Ya está en la nota: se sumará a la línea existente'
                        : '';
                    addBannerMeta.textContent = money(addSelected.price) + ' · ' + addSelected.sizeLabel + extra;
                }

                addBanner.classList.remove('hidden');
                addBanner.classList.add('flex');
            }

            function refreshAddPreview() {
                var p = addSelected;
                var qty = addQty ? Number(addQty.value) : 0;
                var valid = !!p && Number.isInteger(qty) && qty >= 1;

                if (addEquivalent) {
                    var base = valid ? p.conversionFactor * qty : 0;
                    var unit = p ? (p.baseUnitName || p.baseUnitSymbol || '') : '';
                    addEquivalent.textContent = valid
                        ? (base + (unit ? ' ' + pluralize(unit, base) : ''))
                        : '—';
                }

                if (addSubtotalEl) {
                    addSubtotalEl.textContent = valid ? money(qty * p.price) : 'Bs. 0.0';
                }

                if (addSubmit) { addSubmit.disabled = !valid; }
            }

            function resetAddModal() {
                addSelected = null;
                if (addSearch) { addSearch.value = ''; }
                if (addQty) { addQty.value = 1; }
                if (addHiddenId) { addHiddenId.value = ''; }
                closeAddResults();
                updateAddBanner();
                refreshAddPreview();
            }

            if (addSearch) {
                addSearch.addEventListener('input', function () {
                    if (addSearch.value.trim() === '') {
                        addSelected = null;
                        if (addHiddenId) { addHiddenId.value = ''; }
                        closeAddResults();
                        updateAddBanner();
                        refreshAddPreview();
                        return;
                    }

                    renderAddResults(addSearch.value);
                });

                addSearch.addEventListener('keydown', function (event) {
                    if (!addResults || addResults.classList.contains('hidden') || addMatches.length === 0) {
                        if (event.key === 'Enter') { event.preventDefault(); }
                        return;
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        highlightAdd(Math.min(addCursor + 1, addMatches.length - 1));
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        highlightAdd(Math.max(addCursor - 1, 0));
                    } else if (event.key === 'Enter') {
                        event.preventDefault();
                        var pick = addCursor >= 0 ? addMatches[addCursor] : addMatches[0];
                        if (pick) { selectAddPresentation(pick.id); }
                    } else if (event.key === 'Escape') {
                        closeAddResults();
                    }
                });
            }

            addQty?.addEventListener('input', refreshAddPreview);

            addForm?.addEventListener('submit', function (event) {
                if (!addSelected) {
                    event.preventDefault();
                    addSearch?.focus();
                }
            });

            // Abrir el modal (también cuando no hay presentaciones, para avisar).
            document.querySelectorAll('[data-open-add-detail]').forEach(function (button) {
                button.addEventListener('click', function () {
                    resetAddModal();
                    openModal('add-sale-note-detail');
                    if (addSearch) { setTimeout(function () { addSearch.focus(); }, 150); }
                });
            });

            refreshPreview();
            refreshAddPreview();
        })();
    </script>
</x-layouts.sistema>
