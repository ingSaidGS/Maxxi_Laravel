@php
    // Variantes de botón reutilizables (ver skill maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Tamaño de la presentación (badge maxxi-ui).
    $sizeMeta = [
        'grande' => ['label' => 'Grande', 'class' => 'bg-maxxi-orange/15 text-maxxi-orange-dark'],
        'pequeño' => ['label' => 'Pequeño', 'class' => 'bg-maxxi-navy/10 text-maxxi-navy'],
    ];

    // Pluraliza la unidad según la cantidad (vocal final → +s, consonante → +es).
    $pluralize = fn (string $name, int $count): string => $count === 1
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $isToday = $date->toDateString() === $today;
    $isTomorrow = $date->toDateString() === $tomorrow;
@endphp

<x-layouts.sistema title="Preparación de venta">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Preparación de venta</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Productos a alistar, agrupados por presentación, para las notas pendientes con entrega en la fecha elegida.</p>
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
                <input type="date" id="sale-preparation-date" name="date" value="{{ $date->format('Y-m-d') }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="{{ $botones['primario'] }}">Ver preparación</button>
                <a href="{{ route('sale-preparations.index', ['date' => $today]) }}" @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isToday])>Hoy</a>
                <a href="{{ route('sale-preparations.index', ['date' => $tomorrow]) }}" @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isTomorrow])>Mañana</a>
            </div>
        </div>
    </form>

    {{-- Fecha activa --}}
    <div class="mb-4">
        <h2 class="mb-0 text-lg font-semibold text-maxxi-navy">
            Preparación del {{ $date->format('d/m/Y') }}
            @if ($isTomorrow)
                <span class="text-maxxi-orange-dark">· mañana</span>
            @elseif ($isToday)
                <span class="text-maxxi-orange-dark">· hoy</span>
            @endif
        </h2>
    </div>

    @if ($rows->isEmpty())
        {{-- Estado vacío --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <p class="mb-1 mt-3 font-medium text-gray-700">No hay nada que preparar para esta fecha</p>
            <p class="mb-0 text-sm text-gray-500">No hay notas de venta pendientes con entrega agendada el {{ $date->format('d/m/Y') }}.</p>
        </div>
    @else
        {{-- Tarjetas de resumen --}}
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
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

        {{-- Tabla (escritorio) --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block print:block">
            <table class="mb-0 w-full border-collapse text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide">
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Presentación</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Tamaño</th>
                        <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad</th>
                        <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Desglose por nota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php($size = $row['size'] !== null ? ($sizeMeta[$row['size']] ?? null) : null)
                        <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60 print:break-inside-avoid">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $row['product'] }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $row['unit'] }}
                                <span class="text-gray-400">(x{{ $row['factor'] }} {{ $row['base_unit_symbol'] }})</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($size)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $size['class'] }}">{{ $size['label'] }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-800">
                                {{ $row['total'] }} {{ $pluralize($row['unit'], $row['total']) }}
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
                                            · <span class="font-semibold tabular-nums text-gray-700">{{ $item['quantity'] }}</span>
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
            @foreach ($rows as $row)
                @php($size = $row['size'] !== null ? ($sizeMeta[$row['size']] ?? null) : null)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="mb-0 font-semibold text-gray-800">{{ $row['product'] }}</p>
                            <p class="mb-0 mt-0.5 text-sm text-gray-500">{{ $row['unit'] }} (x{{ $row['factor'] }} {{ $row['base_unit_symbol'] }})</p>
                        </div>
                        @if ($size)
                            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $size['class'] }}">{{ $size['label'] }}</span>
                        @endif
                    </div>

                    <div class="mt-3 rounded-lg bg-maxxi-orange/10 px-3 py-2">
                        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Cantidad a preparar</p>
                        <p class="mb-0 mt-0.5 text-lg font-bold tabular-nums text-gray-800">{{ $row['total'] }} {{ $pluralize($row['unit'], $row['total']) }}</p>
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
                                <span class="font-semibold tabular-nums text-gray-700">{{ $item['quantity'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
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
</x-layouts.sistema>
