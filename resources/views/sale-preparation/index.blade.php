@php
    // Variantes de botón reutilizables (ver skill maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Botones de acción por línea de alistamiento (picking). Rellenos y con
    // texto; el COLOR distingue la acción:
    //  - ámbar:    deshacer un escaneo (líneas CON código de barras).
    //  - esmeralda: completar de una vez (líneas SIN código, granel).
    //  - gris:     reiniciar una línea que ya estaba completa.
    $botonPickingBase = 'inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md px-2 py-1 text-xs font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-40 print:hidden';
    $botonDeshacer = $botonPickingBase.' bg-amber-500 hover:bg-amber-600 focus:ring-amber-400';
    $botonCompletar = $botonPickingBase.' bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-400';
    $botonReiniciar = $botonPickingBase.' bg-gray-500 hover:bg-gray-600 focus:ring-gray-400';

    // Pluraliza la unidad según la cantidad (vocal final → +s, consonante → +es).
    $pluralize = fn (string $name, int|float $count): string => (float) $count === 1.0
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    // Entero sin decimales; si no es múltiplo, 1 decimal (igual que sale.show).
    $formatQuantity = fn (int|float $value): string => floor((float) $value) == (float) $value
        ? (string) (int) $value
        : number_format((float) $value, 1, '.', '');

    // Renglones separados por tamaño de presentación (grande / pequeño).
    $largeRows = $rows->where('size', 'grande')->values();
    $smallRows = $rows->where('size', 'pequeño')->values();
    $unsizedRows = $rows->filter(fn (array $row): bool => ! in_array($row['size'], ['grande', 'pequeño'], true))->values();

    // Pequeños: se agrupan por NOTA DE VENTA (el cliente necesita separar su
    // pedido por nota). Se aplana el desglose de cada presentación pequeña para
    // reunir, por nota, todas las presentaciones que lleva. Los grandes no cambian.
    // Cada ítem lleva los datos de alistamiento (picking): la línea de la nota
    // ("detail_id"), su código de barras y el avance (paquetes alistados/pedidos).
    $smallNotes = $smallRows
        ->flatMap(fn (array $row): array => $row['breakdown']
            ->map(fn (array $item): array => [
                'sale_note_id' => $item['sale_note_id'],
                'customer' => $item['customer'],
                'product' => $row['product'],
                'unit' => $row['unit'],
                'factor' => $row['factor'],
                'base_unit_symbol' => $row['base_unit_symbol'],
                'quantity' => $item['quantity'],
                'detail_id' => $item['sale_note_detail_id'],
                'barcode' => $item['barcode'],
                'packages' => $item['packages'],
                'picked' => $item['picked'],
            ])
            ->all())
        ->groupBy('sale_note_id')
        ->map(function ($items): array {
            $items = $items->values();
            $linesTotal = $items->count();
            $linesComplete = $items
                ->filter(fn (array $item): bool => $item['picked'] >= $item['packages'])
                ->count();

            return [
                'sale_note_id' => $items->first()['sale_note_id'],
                'customer' => (string) $items->first()['customer'],
                'items' => $items,
                'lines_total' => $linesTotal,
                'lines_complete' => $linesComplete,
                'packed' => $linesTotal > 0 && $linesComplete === $linesTotal,
            ];
        })
        ->sortBy('sale_note_id')
        ->values();

    // Iconos (Heroicons) por sección: camión para grandes, caja para pequeños.
    $groups = [
        [
            'key' => 'grande',
            'title' => 'Grandes',
            'hint' => 'Empaques grandes',
            'path' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H18.75m-2.25 3.75H14.25m4.5-3.75V6.75A2.25 2.25 0 0016.5 4.5h-6A2.25 2.25 0 008.25 6.75v4.5m0 0H3.375A1.125 1.125 0 002.25 12.375v1.5c0 .621.504 1.125 1.125 1.125h1.5',
            'chip' => 'bg-maxxi-orange/15 text-maxxi-orange-dark',
            'badge' => 'bg-maxxi-orange/15 text-maxxi-orange-dark',
            'rows' => $largeRows,
            'always' => true,
        ],
        [
            'key' => 'pequeño',
            'title' => 'Pequeños',
            'hint' => 'Empaques pequeños',
            'path' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
            'chip' => 'bg-maxxi-navy/10 text-maxxi-navy',
            'badge' => 'bg-maxxi-navy/10 text-maxxi-navy',
            'rows' => $smallRows,
            'notes' => $smallNotes,
            'always' => true,
        ],
    ];

    if ($unsizedRows->isNotEmpty()) {
        $groups[] = [
            'key' => 'sin-tamano',
            'title' => 'Sin tamaño',
            'hint' => 'Presentaciones sin tamaño definido',
            'path' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z',
            'chip' => 'bg-gray-100 text-gray-500',
            'badge' => 'bg-gray-100 text-gray-600',
            'rows' => $unsizedRows,
            'always' => false,
        ];
    }

    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $isToday = $date?->toDateString() === $today;
    $isTomorrow = $date?->toDateString() === $tomorrow;
@endphp

<x-layouts.sistema title="Preparación de venta">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Preparación de venta</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">
                @if ($date)
                    Productos a alistar, agrupados por presentación, para las notas pendientes con entrega el {{ $date->format('d/m/Y') }}.
                @else
                    Productos a alistar, agrupados por presentación, para todas las notas de venta pendientes.
                @endif
            </p>
        </div>

        <button type="button" onclick="window.print()" class="{{ $botones['secundario'] }} w-full sm:w-auto print:hidden">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.05 48.05 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
            </svg>
            Imprimir
        </button>
    </div>

    {{-- Selector de fecha de entrega --}}
    <form method="GET" action="{{ route('sale-preparations.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 print:hidden">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="w-full sm:w-64">
                <label for="sale-preparation-date" class="mb-1 block text-sm font-medium text-gray-700">Fecha de entrega</label>
                <input type="date" id="sale-preparation-date" name="date" value="{{ $date?->format('Y-m-d') }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="{{ $botones['primario'] }}">Ver preparación</button>
                <a href="{{ route('sale-preparations.index', ['date' => $today]) }}" @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isToday])>Hoy</a>
                <a href="{{ route('sale-preparations.index', ['date' => $tomorrow]) }}" @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isTomorrow])>Mañana</a>
                <a href="{{ route('sale-preparations.index') }}" @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $date === null])>Todas</a>
            </div>
        </div>
    </form>

    {{-- Fecha activa --}}
    <div class="mb-4">
        <h2 class="mb-0 text-lg font-semibold text-maxxi-navy">
            @if ($date)
                Preparación del {{ $date->format('d/m/Y') }}
                @if ($isTomorrow)
                    <span class="text-maxxi-orange-dark">· mañana</span>
                @elseif ($isToday)
                    <span class="text-maxxi-orange-dark">· hoy</span>
                @endif
            @else
                Preparación de todas las notas pendientes
            @endif
        </h2>
    </div>

    @if ($rows->isEmpty())
        {{-- Estado vacío --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <p class="mb-1 mt-3 font-medium text-gray-700">
                @if ($date)
                    No hay nada que preparar para esta fecha
                @else
                    No hay nada que preparar
                @endif
            </p>
            <p class="mb-0 text-sm text-gray-500">
                @if ($date)
                    No hay notas de venta pendientes con entrega agendada el {{ $date->format('d/m/Y') }}.
                @else
                    No hay notas de venta en estado pendiente.
                @endif
            </p>
        </div>
    @else
        {{-- Tarjetas de resumen --}}
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Notas</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-800">{{ $notesCount }}</p>
            </div>

            <div class="rounded-xl border border-maxxi-orange/30 bg-maxxi-orange/5 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-maxxi-orange-dark">Presentaciones</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-orange-dark">{{ $rows->count() }}</p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-blue-600">Clientes</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-blue-600">{{ $customersCount }}</p>
            </div>
        </div>

        {{-- Secciones por tamaño: una tabla por grupo (Grandes / Pequeños) --}}
        @foreach ($groups as $group)
            @if ($group['rows']->isNotEmpty() || $group['always'])
                <section class="mb-8">
                    {{-- Encabezado de la sección --}}
                    <div class="mb-3 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $group['chip'] }}" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['path'] }}" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-0 text-base font-semibold text-gray-800">{{ $group['title'] }}</h3>
                            <p class="mb-0 text-xs text-gray-500">{{ $group['hint'] }}</p>
                        </div>
                        <span class="ml-auto inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $group['badge'] }}">
                            {{ $group['rows']->count() }} {{ $group['rows']->count() === 1 ? 'presentación' : 'presentaciones' }}
                        </span>
                    </div>

                    @if ($group['rows']->isEmpty())
                        {{-- Grupo sin renglones --}}
                        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center">
                            <p class="mb-0 text-sm text-gray-500">Nada por preparar en {{ mb_strtolower($group['title']) }}{{ $date ? ' para esta fecha' : '' }}.</p>
                        </div>
                    @elseif ($group['key'] === 'pequeño')
                        {{-- Pequeños: agrupados por nota de venta (cliente + sus presentaciones) --}}
                        {{-- Consola de alistamiento (picking): selecciona la nota y escanea sus empaques --}}
                        <div class="mb-3 rounded-xl border border-maxxi-navy/20 bg-white p-4 shadow-sm print:hidden" data-picking-console>
                            <div class="flex flex-col gap-3">
                                <div class="w-full sm:max-w-sm">
                                    <label for="picking-note" class="mb-1 block text-sm font-medium text-gray-700">Nota a alistar</label>
                                    <select id="picking-note" data-picking-note
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                                        <option value="">Selecciona una nota…</option>
                                        @foreach ($smallNotes as $note)
                                            <option value="{{ $note['sale_note_id'] }}"
                                                    data-label="NV #{{ $note['sale_note_id'] }}@if ($note['customer'] !== '—') · {{ $note['customer'] }}@endif">NV #{{ $note['sale_note_id'] }}@if ($note['customer'] !== '—') · {{ $note['customer'] }}@endif — {{ $note['lines_complete'] }}/{{ $note['lines_total'] }} alistadas{{ $note['packed'] ? ' (empacada)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="w-full sm:flex-1">
                                    <label for="picking-input" class="mb-1 block text-sm font-medium text-gray-700">Escanea el código de barras</label>
                                    <div class="flex items-stretch gap-2">
                                        <input type="text" id="picking-input" data-picking-input autocomplete="off" disabled
                                               placeholder="Primero selecciona una nota…"
                                               class="block w-full min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange disabled:cursor-not-allowed disabled:bg-gray-100" />
                                        <button type="button" id="picking-scan-open" aria-label="Escanear con la cámara" title="Escanear con la cámara"
                                                class="inline-flex w-10 shrink-0 items-center justify-center rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5v9M6.75 7.5v9M9.75 7.5v2.25M9.75 14.25v2.25M12.75 7.5v2.25M12.75 14.25v2.25M15.75 7.5v9M18.75 7.5v2.25M18.75 14.25v2.25M21 9v6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p class="mb-0 mt-2 text-sm text-gray-500" data-picking-feedback role="status" aria-live="polite">
                                Selecciona una nota y escanea el código de barras de cada empaque pequeño.
                            </p>
                        </div>

                        {{-- Modal: escanear con la cámara (reutiliza MaxxiBarcodeScanner) --}}
                        <x-modal name="picking-scanner" max-width="md">
                            <div class="p-5 sm:p-6"
                                 x-init="$watch('show', value => {
                                    if (value) { setTimeout(() => window.startPickingScanner && window.startPickingScanner(), 150); }
                                    else { window.stopPickingScanner && window.stopPickingScanner(); }
                                 })">
                                <div class="flex items-start gap-4">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-navy/5 text-maxxi-navy">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5v9M6.75 7.5v9M9.75 7.5v2.25M9.75 14.25v2.25M12.75 7.5v2.25M12.75 14.25v2.25M15.75 7.5v9M18.75 7.5v2.25M18.75 14.25v2.25M21 9v6" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h2 class="mb-1 text-lg font-semibold text-gray-800">Escanear código de barras</h2>
                                        <p class="mb-0 text-sm text-gray-600">Apunta la cámara al código del empaque. Al leerlo se registrará el alistamiento de la nota seleccionada.</p>
                                    </div>
                                </div>

                                <div id="picking-reader" class="mt-4 aspect-video w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-900"></div>
                                <p id="picking-status" class="mt-2 mb-0 text-sm text-gray-500">Iniciando la cámara…</p>

                                {{-- Entrada manual como respaldo (si la cámara no está disponible) --}}
                                <div class="mt-4 border-t border-gray-100 pt-4">
                                    <label for="picking-manual" class="mb-1 block text-sm font-medium text-gray-700">Escribir el código manualmente</label>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <input type="text" id="picking-manual" autocomplete="off" spellcheck="false"
                                               placeholder="Ej. 7501234567890"
                                               class="block w-full flex-1 rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                                        <button type="button" id="picking-manual-apply" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                                            Usar código
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'picking-scanner')">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </x-modal>

                        {{-- Tabla (escritorio) --}}
                        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block print:block">
                            <table class="mb-0 w-full border-collapse text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide">
                                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Presentación</th>
                                        <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad</th>
                                        <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Alistado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['notes'] as $note)
                                        {{-- Encabezado de la nota: aquí se separa el pedido por nota/cliente --}}
                                        <tr class="border-t border-gray-200 bg-maxxi-navy/5" data-picking-note-header="{{ $note['sale_note_id'] }}">
                                            <td colspan="4" class="px-4 py-2.5">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-semibold text-maxxi-navy">
                                                        @if ($note['sale_note_id'])
                                                            <a href="{{ route('sale-notes.show', $note['sale_note_id']) }}" class="hover:text-maxxi-orange">NV #{{ $note['sale_note_id'] }}</a>
                                                        @else
                                                            NV —
                                                        @endif
                                                    </span>
                                                    @if ($note['customer'] !== '—')
                                                        <span class="text-sm text-gray-600">Cliente: <span class="font-medium text-gray-800">{{ $note['customer'] }}</span></span>
                                                    @endif
                                                    <span class="ml-auto inline-flex items-center gap-2">
                                                        <span class="text-xs text-gray-500" data-picking-note-progress="{{ $note['sale_note_id'] }}">{{ $note['lines_complete'] }}/{{ $note['lines_total'] }} alistadas</span>
                                                        <span data-picking-note-status="{{ $note['sale_note_id'] }}"
                                                              @class([
                                                                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                                  'bg-gray-100 text-gray-600' => ! $note['packed'],
                                                                  'bg-emerald-100 text-emerald-700' => $note['packed'],
                                                              ])>{{ $note['packed'] ? 'Empacado' : 'Pendiente' }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        @foreach ($note['items'] as $item)
                                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60 print:break-inside-avoid"
                                                data-picking-item
                                                data-detail-id="{{ $item['detail_id'] }}"
                                                data-note-id="{{ $note['sale_note_id'] }}"
                                                data-barcode="{{ $item['barcode'] }}"
                                                data-quantity="{{ $item['packages'] }}"
                                                data-picked="{{ $item['picked'] }}">
                                                <td class="px-4 py-3 font-medium text-gray-800">{{ $item['product'] }}</td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    {{ $item['unit'] }}
                                                    <span class="text-gray-400">(x{{ $item['factor'] }} {{ $item['base_unit_symbol'] }})</span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-800">
                                                    {{ $formatQuantity($item['quantity']) }} {{ $pluralize($item['unit'], $item['quantity']) }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        <span data-picking-count @class(['font-semibold tabular-nums', 'text-emerald-600' => $item['picked'] >= $item['packages'], 'text-maxxi-orange-dark' => $item['picked'] < $item['packages']])>{{ $item['picked'] }}/{{ $item['packages'] }}</span>
                                                        @if (filled($item['barcode']))
                                                            {{-- Con código de barras: el deshacer resta 1 al último escaneo. --}}
                                                            <button type="button" data-picking-undo @disabled($item['picked'] <= 0)
                                                                    aria-label="Deshacer un escaneo" title="Deshacer un escaneo"
                                                                    class="{{ $botonDeshacer }}">
                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                                                Deshacer
                                                            </button>
                                                        @else
                                                            {{-- Sin código (granel): completa o reinicia el alistado de una vez. --}}
                                                            <button type="button" data-picking-auto
                                                                    data-class-complete="{{ $botonCompletar }}"
                                                                    data-class-reset="{{ $botonReiniciar }}"
                                                                    aria-label="{{ $item['picked'] >= $item['packages'] ? 'Reiniciar el alistado' : 'Completar el alistado' }}"
                                                                    title="{{ $item['picked'] >= $item['packages'] ? 'Reiniciar el alistado' : 'Completar de una vez' }}"
                                                                    class="{{ $item['picked'] >= $item['packages'] ? $botonReiniciar : $botonCompletar }}">
                                                                <svg data-picking-auto-check @class(['h-3.5 w-3.5', 'hidden' => $item['picked'] >= $item['packages']]) fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                                <svg data-picking-auto-reset @class(['h-3.5 w-3.5', 'hidden' => $item['picked'] < $item['packages']]) fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                                <span data-picking-auto-label>{{ $item['picked'] >= $item['packages'] ? 'Reiniciar' : 'Completar' }}</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Tarjetas (móvil) --}}
                        <div class="space-y-3 md:hidden print:hidden">
                            @foreach ($group['notes'] as $note)
                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-maxxi-navy/5 px-4 py-3" data-picking-note-header="{{ $note['sale_note_id'] }}">
                                        <span class="font-semibold text-maxxi-navy">
                                            @if ($note['sale_note_id'])
                                                <a href="{{ route('sale-notes.show', $note['sale_note_id']) }}" class="hover:text-maxxi-orange">NV #{{ $note['sale_note_id'] }}</a>
                                            @else
                                                NV —
                                            @endif
                                        </span>
                                        @if ($note['customer'] !== '—')
                                            <span class="text-sm text-gray-600">Cliente: <span class="font-medium text-gray-800">{{ $note['customer'] }}</span></span>
                                        @endif
                                        <span class="ml-auto inline-flex items-center gap-2">
                                            <span class="text-xs text-gray-500" data-picking-note-progress="{{ $note['sale_note_id'] }}">{{ $note['lines_complete'] }}/{{ $note['lines_total'] }} alistadas</span>
                                            <span data-picking-note-status="{{ $note['sale_note_id'] }}"
                                                  @class([
                                                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                      'bg-gray-100 text-gray-600' => ! $note['packed'],
                                                      'bg-emerald-100 text-emerald-700' => $note['packed'],
                                                  ])>{{ $note['packed'] ? 'Empacado' : 'Pendiente' }}</span>
                                        </span>
                                    </div>

                                    <ul class="mb-0 list-none space-y-1 p-4">
                                        @foreach ($note['items'] as $item)
                                            <li class="flex items-start justify-between gap-3"
                                                data-picking-item
                                                data-detail-id="{{ $item['detail_id'] }}"
                                                data-note-id="{{ $note['sale_note_id'] }}"
                                                data-barcode="{{ $item['barcode'] }}"
                                                data-quantity="{{ $item['packages'] }}"
                                                data-picked="{{ $item['picked'] }}">
                                                <div>
                                                    <p class="mb-0 font-semibold text-gray-800">{{ $item['product'] }}</p>
                                                    <p class="mb-0 mt-0.5 text-xs text-gray-500">{{ $item['unit'] }} (x{{ $item['factor'] }} {{ $item['base_unit_symbol'] }})</p>
                                                </div>
                                                <div class="flex shrink-0 flex-col items-end gap-1">
                                                    <span class="font-semibold tabular-nums text-gray-800">{{ $formatQuantity($item['quantity']) }} {{ $pluralize($item['unit'], $item['quantity']) }}</span>
                                                    <span class="inline-flex items-center gap-2">
                                                        <span data-picking-count @class(['font-semibold tabular-nums', 'text-emerald-600' => $item['picked'] >= $item['packages'], 'text-maxxi-orange-dark' => $item['picked'] < $item['packages']])>{{ $item['picked'] }}/{{ $item['packages'] }}</span>
                                                        @if (filled($item['barcode']))
                                                            {{-- Con código de barras: el deshacer resta 1 al último escaneo. --}}
                                                            <button type="button" data-picking-undo @disabled($item['picked'] <= 0)
                                                                    aria-label="Deshacer un escaneo" title="Deshacer un escaneo"
                                                                    class="{{ $botonDeshacer }}">
                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                                                Deshacer
                                                            </button>
                                                        @else
                                                            {{-- Sin código (granel): completa o reinicia el alistado de una vez. --}}
                                                            <button type="button" data-picking-auto
                                                                    data-class-complete="{{ $botonCompletar }}"
                                                                    data-class-reset="{{ $botonReiniciar }}"
                                                                    aria-label="{{ $item['picked'] >= $item['packages'] ? 'Reiniciar el alistado' : 'Completar el alistado' }}"
                                                                    title="{{ $item['picked'] >= $item['packages'] ? 'Reiniciar el alistado' : 'Completar de una vez' }}"
                                                                    class="{{ $item['picked'] >= $item['packages'] ? $botonReiniciar : $botonCompletar }}">
                                                                <svg data-picking-auto-check @class(['h-3.5 w-3.5', 'hidden' => $item['picked'] >= $item['packages']]) fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                                <svg data-picking-auto-reset @class(['h-3.5 w-3.5', 'hidden' => $item['picked'] < $item['packages']]) fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                                <span data-picking-auto-label>{{ $item['picked'] >= $item['packages'] ? 'Reiniciar' : 'Completar' }}</span>
                                                            </button>
                                                        @endif
                                                    </span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Tabla (escritorio) --}}
                        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block print:block">
                            <table class="mb-0 w-full border-collapse text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide">
                                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Presentación</th>
                                        <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad</th>
                                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Desglose por nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['rows'] as $row)
                                        <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60 print:break-inside-avoid">
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $row['product'] }}</td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $row['unit'] }}
                                                <span class="text-gray-400">(x{{ $row['factor'] }} {{ $row['base_unit_symbol'] }})</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-800">
                                                {{ $formatQuantity($row['total']) }} {{ $pluralize($row['unit'], $row['total']) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <ul class="mb-0 list-none space-y-0.5 p-0">
                                                    @foreach ($row['breakdown'] as $item)
                                                        <li class="text-xs text-gray-500">
                                                            @if ($item['sale_note_id'])
                                                                <a href="{{ route('sale-notes.show', $item['sale_note_id']) }}" class="font-medium text-maxxi-navy hover:text-maxxi-orange">NV #{{ $item['sale_note_id'] }}</a>
                                                            @else
                                                                <span class="font-medium text-maxxi-navy">NV —</span>
                                                            @endif
                                                            · {{ $item['customer'] }}
                                                            · <span class="font-semibold tabular-nums text-gray-700">{{ $formatQuantity($item['quantity']) }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Tarjetas (móvil) --}}
                        <div class="space-y-3 md:hidden print:hidden">
                            @foreach ($group['rows'] as $row)
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="mb-0 font-semibold text-gray-800">{{ $row['product'] }}</p>
                                            <p class="mb-0 mt-0.5 text-sm text-gray-500">{{ $row['unit'] }} (x{{ $row['factor'] }} {{ $row['base_unit_symbol'] }})</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $group['badge'] }}">{{ $group['title'] }}</span>
                                    </div>

                                    <div class="mt-3 rounded-lg bg-maxxi-orange/10 px-3 py-2">
                                        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Cantidad a preparar</p>
                                        <p class="mb-0 mt-0.5 text-lg font-bold tabular-nums text-gray-800">{{ $formatQuantity($row['total']) }} {{ $pluralize($row['unit'], $row['total']) }}</p>
                                    </div>

                                    <ul class="mb-0 mt-3 list-none space-y-1 p-0">
                                        @foreach ($row['breakdown'] as $item)
                                            <li class="flex items-center justify-between gap-3 text-xs text-gray-500">
                                                <span>
                                                    @if ($item['sale_note_id'])
                                                        <a href="{{ route('sale-notes.show', $item['sale_note_id']) }}" class="font-medium text-maxxi-navy hover:text-maxxi-orange">NV #{{ $item['sale_note_id'] }}</a>
                                                    @else
                                                        <span class="font-medium text-maxxi-navy">NV —</span>
                                                    @endif
                                                    · {{ $item['customer'] }}
                                                </span>
                                                <span class="font-semibold tabular-nums text-gray-700">{{ $formatQuantity($item['quantity']) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        @endforeach
    @endif

    <style>
        @media print {
            body {
                background: #fff !important;
            }

            main {
                padding: 0 !important;
            }
        }
    </style>

    @if ($smallNotes->isNotEmpty())
        <script>
            (function () {
                var consoleEl = document.querySelector('[data-picking-console]');
                if (!consoleEl) {
                    return;
                }

                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var noteSelect = consoleEl.querySelector('[data-picking-note]');
                var input = consoleEl.querySelector('[data-picking-input]');
                var feedback = consoleEl.querySelector('[data-picking-feedback]');

                var scanUrl = @json(route('sale-preparations.scan', ['sale_note' => '__NOTE__']));
                var unscanUrl = @json(route('sale-preparations.details.unscan', ['sale_note' => '__NOTE__', 'sale_note_detail' => '__DETAIL__']));
                var completeUrl = @json(route('sale-preparations.details.complete', ['sale_note' => '__NOTE__', 'sale_note_detail' => '__DETAIL__']));
                var resetUrl = @json(route('sale-preparations.details.reset', ['sale_note' => '__NOTE__', 'sale_note_detail' => '__DETAIL__']));

                function setFeedback(message, tone) {
                    var colors = { error: 'text-red-600', success: 'text-emerald-600', info: 'text-gray-500' };
                    feedback.textContent = message;
                    feedback.className = 'mb-0 mt-2 text-sm ' + (colors[tone] || colors.info);
                }

                function clearHighlight() {
                    document.querySelectorAll('[data-picking-note-header]').forEach(function (element) {
                        element.style.backgroundColor = '';
                    });
                }

                function applyDetail(detail) {
                    document.querySelectorAll('[data-picking-item][data-detail-id="' + detail.id + '"]').forEach(function (element) {
                        element.setAttribute('data-picked', detail.picked);

                        var count = element.querySelector('[data-picking-count]');
                        if (count) {
                            count.textContent = detail.picked + '/' + detail.quantity;
                            count.className = 'font-semibold tabular-nums ' + (detail.completed ? 'text-emerald-600' : 'text-maxxi-orange-dark');
                        }

                        var undo = element.querySelector('[data-picking-undo]');
                        if (undo) {
                            undo.disabled = detail.picked <= 0;
                        }

                        var auto = element.querySelector('[data-picking-auto]');
                        if (auto) {
                            auto.setAttribute('title', detail.completed ? 'Reiniciar el alistado' : 'Completar de una vez');
                            auto.setAttribute('aria-label', detail.completed ? 'Reiniciar el alistado' : 'Completar el alistado');

                            // El color del botón también sigue al estado: verde al completar, gris al reiniciar.
                            var classes = detail.completed ? auto.getAttribute('data-class-reset') : auto.getAttribute('data-class-complete');
                            if (classes) { auto.className = classes; }

                            var checkIcon = auto.querySelector('[data-picking-auto-check]');
                            var resetIcon = auto.querySelector('[data-picking-auto-reset]');
                            if (checkIcon) { checkIcon.classList.toggle('hidden', detail.completed); }
                            if (resetIcon) { resetIcon.classList.toggle('hidden', !detail.completed); }

                            var label = auto.querySelector('[data-picking-auto-label]');
                            if (label) { label.textContent = detail.completed ? 'Reiniciar' : 'Completar'; }
                        }
                    });
                }

                function applyNote(note) {
                    document.querySelectorAll('[data-picking-note-progress="' + note.sale_note_id + '"]').forEach(function (element) {
                        element.textContent = note.lines_complete + '/' + note.lines_total + ' alistadas';
                    });

                    document.querySelectorAll('[data-picking-note-status="' + note.sale_note_id + '"]').forEach(function (element) {
                        element.textContent = note.packed ? 'Empacado' : 'Pendiente';
                        element.className = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
                            + (note.packed ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600');
                    });

                    var option = noteSelect.querySelector('option[value="' + note.sale_note_id + '"]');
                    if (option) {
                        option.textContent = option.getAttribute('data-label') + ' — ' + note.lines_complete + '/' + note.lines_total
                            + ' alistadas' + (note.packed ? ' (empacada)' : '');
                    }
                }

                function post(url, payload) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : ''
                        },
                        body: JSON.stringify(payload || {})
                    }).then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (data) {
                            if (!response.ok) {
                                var errors = data.errors || {};
                                var firstKey = Object.keys(errors)[0];
                                throw new Error((firstKey && errors[firstKey][0]) || 'No se pudo registrar la acción.');
                            }
                            return data;
                        });
                    });
                }

                noteSelect.addEventListener('change', function () {
                    input.value = '';

                    if (!noteSelect.value) {
                        input.disabled = true;
                        input.placeholder = 'Primero selecciona una nota…';
                        clearHighlight();
                        setFeedback('Selecciona una nota y escanea el código de barras de cada empaque pequeño.', 'info');
                        return;
                    }

                    input.disabled = false;
                    input.placeholder = 'Escanea aquí…';
                    clearHighlight();

                    document.querySelectorAll('[data-picking-note-header="' + noteSelect.value + '"]').forEach(function (header) {
                        header.style.backgroundColor = '#FEF0E1';
                    });

                    input.focus();
                    setFeedback('Nota seleccionada. Escanea los empaques pequeños.', 'info');
                });

                function scan() {
                    var code = input.value.trim();

                    if (!noteSelect.value) {
                        setFeedback('Selecciona primero una nota.', 'error');
                        return;
                    }
                    if (code === '') {
                        return;
                    }

                    input.value = '';

                    post(scanUrl.replace('__NOTE__', noteSelect.value), { barcode: code })
                        .then(function (data) {
                            applyDetail(data.detail);
                            applyNote(data.note);

                            if (data.exhausted) {
                                setFeedback('Ese producto ya estaba completo.', 'error');
                            } else if (data.note.packed) {
                                setFeedback('¡Nota completa y empacada!', 'success');
                            } else {
                                setFeedback('Alistado: ' + data.detail.picked + '/' + data.detail.quantity, 'success');
                            }
                        })
                        .catch(function (error) {
                            setFeedback(error.message, 'error');
                        })
                        .finally(function () {
                            input.focus();
                        });
                }

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        scan();
                    }
                });

                // Contexto de una línea de alistamiento a partir de su botón.
                function detailContext(button) {
                    var item = button.closest('[data-picking-item]');
                    if (!item) {
                        return null;
                    }

                    var noteId = item.getAttribute('data-note-id');
                    var detailId = item.getAttribute('data-detail-id');

                    if (!noteId || !detailId) {
                        return null;
                    }

                    return { item: item, noteId: noteId, detailId: detailId };
                }

                function detailUrl(template, context) {
                    return template.replace('__NOTE__', context.noteId).replace('__DETAIL__', context.detailId);
                }

                document.addEventListener('click', function (event) {
                    var undo = event.target.closest('[data-picking-undo]');
                    if (undo) {
                        var undoContext = detailContext(undo);
                        if (!undoContext) { return; }

                        post(detailUrl(unscanUrl, undoContext))
                            .then(function (data) {
                                applyDetail(data.detail);
                                applyNote(data.note);
                                setFeedback('Escaneo deshecho: ' + data.detail.picked + '/' + data.detail.quantity, 'info');
                            })
                            .catch(function (error) {
                                setFeedback(error.message, 'error');
                            });
                        return;
                    }

                    // Botón de empaques sin código: completa o reinicia según su estado.
                    var auto = event.target.closest('[data-picking-auto]');
                    if (!auto) {
                        return;
                    }

                    var autoContext = detailContext(auto);
                    if (!autoContext) { return; }

                    var completing = Number(autoContext.item.getAttribute('data-picked'))
                        < Number(autoContext.item.getAttribute('data-quantity'));

                    post(detailUrl(completing ? completeUrl : resetUrl, autoContext))
                        .then(function (data) {
                            applyDetail(data.detail);
                            applyNote(data.note);

                            if (completing) {
                                setFeedback('Línea completada: ' + data.detail.picked + '/' + data.detail.quantity, 'success');
                            } else {
                                setFeedback('Línea reiniciada: ' + data.detail.picked + '/' + data.detail.quantity, 'info');
                            }
                        })
                        .catch(function (error) {
                            setFeedback(error.message, 'error');
                        });
                });

                // --- Escáner con cámara (reutiliza resources/js/barcode-scanner.js) ---
                var scanOpen = document.getElementById('picking-scan-open');
                var scannerStatus = document.getElementById('picking-status');
                var manualCode = document.getElementById('picking-manual');
                var manualApply = document.getElementById('picking-manual-apply');

                function setScannerStatus(message, isError) {
                    if (!scannerStatus) { return; }
                    scannerStatus.textContent = message;
                    scannerStatus.classList.toggle('text-red-600', !!isError);
                    scannerStatus.classList.toggle('text-gray-500', !isError);
                }

                function hasActiveNote() {
                    if (!noteSelect.value) {
                        setFeedback('Selecciona primero una nota para escanear.', 'error');
                        noteSelect.focus();
                        return false;
                    }
                    return true;
                }

                function barcodeErrorMessage(error) {
                    var name = error && error.name ? error.name : '';
                    if (name === 'NotAllowedError' || name === 'SecurityError') {
                        return 'Permiso de cámara denegado. Puedes escribir el código manualmente.';
                    }
                    if (name === 'NotFoundError' || name === 'OverconstrainedError') {
                        return 'No se encontró una cámara disponible. Escribe el código manualmente.';
                    }
                    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                        return 'El acceso a la cámara requiere una conexión segura (HTTPS). Puedes escribir el código manualmente.';
                    }
                    return 'No se pudo iniciar la cámara. Escribe el código manualmente.';
                }

                // Aplica un código leído por cámara o escrito a mano al flujo de escaneo.
                function applyCameraCode(code) {
                    var clean = (code || '').trim();
                    if (clean === '' || !hasActiveNote()) { return; }
                    input.value = clean;
                    scan();
                }

                window.startPickingScanner = function () {
                    if (manualCode) { manualCode.value = ''; }
                    setScannerStatus('Iniciando la cámara…', false);

                    if (!window.MaxxiBarcodeScanner) {
                        setScannerStatus('El escáner aún no está listo. Escribe el código manualmente.', true);
                        return;
                    }

                    window.MaxxiBarcodeScanner.start('picking-reader', function (decodedText) {
                        window.stopPickingScanner();
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'picking-scanner' }));
                        applyCameraCode(decodedText);
                    }, function (error) {
                        setScannerStatus(barcodeErrorMessage(error), true);
                    });
                };

                window.stopPickingScanner = function () {
                    if (window.MaxxiBarcodeScanner) { window.MaxxiBarcodeScanner.stop(); }
                };

                if (scanOpen) {
                    scanOpen.addEventListener('click', function () {
                        if (!hasActiveNote()) { return; }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'picking-scanner' }));
                    });
                }

                if (manualApply) {
                    manualApply.addEventListener('click', function () {
                        applyCameraCode(manualCode ? manualCode.value : '');
                        if (noteSelect.value) {
                            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'picking-scanner' }));
                        }
                    });
                }

                if (manualCode) {
                    manualCode.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            if (manualApply) { manualApply.click(); }
                        }
                    });
                }
            })();
        </script>
    @endif

    @vite('resources/js/barcode-scanner.js')
</x-layouts.sistema>
