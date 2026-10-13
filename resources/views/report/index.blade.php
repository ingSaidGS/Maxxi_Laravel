@php
    // Variantes de botón reutilizables (ver skill maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Formato de moneda (boliviano) y de cantidades.
    $money = fn (float|int|string|null $value): string => 'Bs. '.number_format((float) $value, 1, '.', ',');
    $qty = fn (float|int|string|null $value): string => number_format((float) $value, 0, '.', ',');
    $fecha = fn (string $value): string => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');

    // Atajos de rango para el filtro.
    $today = \Illuminate\Support\Carbon::today();
    $weekStart = $today->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $monthStart = $today->copy()->startOfMonth();

    $isToday = $from->isSameDay($today) && $to->isSameDay($today);
    $isWeek = $from->isSameDay($weekStart) && $to->isSameDay($today);
    $isMonth = $from->isSameDay($monthStart) && $to->isSameDay($today);
@endphp

<x-layouts.sistema title="Reportes">
    {{-- Estilos solo para impresión / «Guardar como PDF» (A4). --}}
    <style>
        @media print {
            @page { size: A4 portrait; margin: 12mm; }

            body { background: #fff !important; }

            /* Conserva los colores de tarjetas y cabeceras al guardar como PDF. */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

            /* Cada sección intenta no partirse entre páginas. */
            section { break-inside: avoid; page-break-inside: avoid; }

            /* Sin sombras en papel. */
            .shadow, .shadow-sm, .shadow-md { box-shadow: none !important; }
        }
    </style>

    {{-- Encabezado de página + impresión --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Reportes</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Reporte económico, ventas por día, top de productos y cobros por usuario.</p>
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center print:hidden">
            <a href="{{ route('reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Excel
            </a>
            <button type="button" onclick="window.print()"
                    title="Abre el diálogo de impresión: elige «Guardar como PDF»"
                    class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3 3m0 0l-3-3m3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                PDF
            </button>
        </div>
    </div>

    {{-- Filtro por rango de fechas --}}
    <form method="GET" action="{{ route('reports.index') }}" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 print:hidden">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="w-full sm:w-44">
                <label for="report-from" class="mb-1 block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" id="report-from" name="from" value="{{ $from->format('Y-m-d') }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>
            <div class="w-full sm:w-44">
                <label for="report-to" class="mb-1 block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" id="report-to" name="to" value="{{ $to->format('Y-m-d') }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="{{ $botones['primario'] }}">Ver reportes</button>
                <a href="{{ route('reports.index', ['from' => $today->toDateString(), 'to' => $today->toDateString()]) }}"
                   @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isToday])>Hoy</a>
                <a href="{{ route('reports.index', ['from' => $weekStart->toDateString(), 'to' => $today->toDateString()]) }}"
                   @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isWeek])>Esta semana</a>
                <a href="{{ route('reports.index', ['from' => $monthStart->toDateString(), 'to' => $today->toDateString()]) }}"
                   @class([$botones['marino'], 'ring-2 ring-maxxi-orange ring-offset-1' => $isMonth])>Este mes</a>
            </div>
        </div>
    </form>

    {{-- Rango activo --}}
    <div class="mb-4">
        <h2 class="mb-0 text-lg font-semibold text-maxxi-navy">
            Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}
        </h2>
    </div>

    {{-- 1. Reporte económico --}}
    <section aria-labelledby="report-economic-title">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </span>
            <h2 id="report-economic-title" class="mb-0 text-lg font-semibold text-maxxi-navy">Reporte económico</h2>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {{-- Ventas netas --}}
            <div class="rounded-xl border border-maxxi-orange/30 bg-maxxi-orange/5 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-maxxi-orange-dark">Ventas netas</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-orange-dark">{{ $money($economic['net']) }}</p>
                <p class="mb-0 mt-0.5 text-xs text-gray-500">Bruto {{ $money($economic['gross']) }}</p>
            </div>

            {{-- Efectivo --}}
            <div class="rounded-xl border border-green-200 bg-green-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-green-600">Efectivo</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-green-600">{{ $money($economic['cash']) }}</p>
            </div>

            {{-- QR --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-blue-600">QR</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-blue-600">{{ $money($economic['qr']) }}</p>
            </div>

            {{-- Fiado --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-amber-600">Fiado</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-amber-600">{{ $money($economic['debt']) }}</p>
            </div>

            {{-- N° de ventas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Ventas</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-800">{{ $qty($economic['sales_count']) }}</p>
                <p class="mb-0 mt-0.5 text-xs text-gray-500">Ticket prom. {{ $money($economic['avg_ticket']) }}</p>
            </div>

            {{-- Descuentos --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Descuentos</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-700">{{ $money($economic['discount']) }}</p>
            </div>

            {{-- Inversión (costo de lo vendido) --}}
            <div class="rounded-xl border border-maxxi-slate/30 bg-maxxi-slate/5 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-maxxi-slate">Inversión</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-slate/10 text-maxxi-slate" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-slate">{{ $money($economic['cost']) }}</p>
                <p class="mb-0 mt-0.5 text-xs text-gray-500">{{ number_format($economic['cost_share'], 1, '.', ',') }}% de las ventas</p>
            </div>

            {{-- Ganancia bruta --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-emerald-600">Ganancia bruta</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-emerald-700">{{ $money($economic['profit']) }}</p>
                <p class="mb-0 mt-0.5 text-xs text-gray-500">Margen {{ number_format($economic['margin'], 1, '.', ',') }}%</p>
            </div>
        </div>
    </section>

    {{-- 2. Ventas por día --}}
    <section aria-labelledby="report-daily-title" class="mt-8">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </span>
            <h2 id="report-daily-title" class="mb-0 text-lg font-semibold text-maxxi-navy">Ventas por día</h2>
        </div>

        @if ($byDay->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                <p class="mb-1 font-medium text-gray-700">Sin ventas en el rango</p>
                <p class="mb-0 text-sm text-gray-500">No hay ventas registradas entre las fechas seleccionadas.</p>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Fecha</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Ventas</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Netas</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Efectivo</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">QR</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Fiado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byDay as $row)
                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $fecha($row->day) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $qty($row->sales_count) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($row->net) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-green-600">{{ $money($row->cash) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-blue-600">{{ $money($row->qr) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-amber-600">{{ $money($row->debt) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($byDay as $row)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="mb-0 font-semibold text-gray-800">{{ $fecha($row->day) }}</p>
                            <span class="inline-flex shrink-0 items-center rounded-full bg-maxxi-navy/10 px-2.5 py-0.5 text-xs font-medium text-maxxi-navy">{{ $qty($row->sales_count) }} ventas</span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-maxxi-orange/10 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Netas</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->net) }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-green-600">Efectivo</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->cash) }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-blue-600">QR</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->qr) }}</p>
                            </div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-amber-600">Fiado</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->debt) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 3. Top productos --}}
    <section aria-labelledby="report-top-title" class="mt-8">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
            </span>
            <h2 id="report-top-title" class="mb-0 text-lg font-semibold text-maxxi-navy">Top productos vendidos</h2>
            <span class="text-xs text-gray-400">(ordenado por cantidad)</span>
        </div>

        @if ($topProducts->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                <p class="mb-1 font-medium text-gray-700">Sin productos vendidos</p>
                <p class="mb-0 text-sm text-gray-500">No hay líneas de venta en el rango seleccionado.</p>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">#</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad (unidad base)</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $row)
                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 tabular-nums text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $row->product_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-800">
                                    {{ $qty($row->quantity) }}
                                    @if ($row->unit_symbol)
                                        <span class="ml-1 text-xs font-normal text-gray-500">{{ $row->unit_symbol }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($row->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($topProducts as $row)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-maxxi-navy/10 text-xs font-semibold text-maxxi-navy">{{ $loop->iteration }}</span>
                                <p class="mb-0 font-semibold text-gray-800">{{ $row->product_name }}</p>
                            </div>
                            <span class="mb-0 shrink-0 text-sm font-bold tabular-nums text-maxxi-orange-dark">{{ $money($row->revenue) }}</span>
                        </div>
                        <p class="mb-0 mt-2 text-sm text-gray-500">
                            Cantidad:
                            <span class="font-semibold tabular-nums text-gray-800">{{ $qty($row->quantity) }}</span>
                            @if ($row->unit_symbol)
                                <span class="text-gray-500">{{ $row->unit_symbol }}</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 4. Cobros por usuario --}}
    <section aria-labelledby="report-user-title" class="mt-8">
        <div class="mb-3 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </span>
            <h2 id="report-user-title" class="mb-0 text-lg font-semibold text-maxxi-navy">Ventas por usuario</h2>
        </div>

        @if ($byUser->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                <p class="mb-1 font-medium text-gray-700">Sin ventas por usuario</p>
                <p class="mb-0 text-sm text-gray-500">Ningún usuario registró ventas en el rango seleccionado.</p>
            </div>
        @else
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Usuario</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Ventas</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Netas</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Efectivo</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">QR</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Fiado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byUser as $row)
                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $row->user_name }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $qty($row->sales_count) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($row->net) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-green-600">{{ $money($row->cash) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-blue-600">{{ $money($row->qr) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-amber-600">{{ $money($row->debt) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($byUser as $row)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="mb-0 font-semibold text-gray-800">{{ $row->user_name }}</p>
                            <span class="inline-flex shrink-0 items-center rounded-full bg-maxxi-navy/10 px-2.5 py-0.5 text-xs font-medium text-maxxi-navy">{{ $qty($row->sales_count) }} ventas</span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-maxxi-orange/10 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Netas</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->net) }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-green-600">Efectivo</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->cash) }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-blue-600">QR</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->qr) }}</p>
                            </div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-amber-600">Fiado</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">{{ $money($row->debt) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.sistema>
