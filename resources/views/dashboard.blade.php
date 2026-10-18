@php
    // Variantes de botón reutilizables (ver skill maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Formato de moneda (boliviano) y de cantidades.
    $money = fn (float|int|string|null $value): string => 'Bs. '.number_format((float) $value, 1, '.', ',');
    $qty = fn (float|int|string|null $value): string => number_format((float) $value, 0, '.', ',');

    // Días de la semana en español (evita depender del locale de Carbon).
    $dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    // Tarjetas KPI del día. Cada una define color, subtítulo e icono.
    $kpis = [
        [
            'label' => 'Ventas netas',
            'value' => $money($economic['net']),
            'subtitle' => 'Bruto '.$money($economic['gross']),
            'card' => 'border-maxxi-orange/30 bg-maxxi-orange/5',
            'iconBox' => 'bg-maxxi-orange/15 text-maxxi-orange-dark',
            'labelClass' => 'text-maxxi-orange-dark',
            'valueClass' => 'text-maxxi-orange-dark',
            'paths' => ['M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
        [
            'label' => 'Efectivo',
            'value' => $money($economic['cash']),
            'subtitle' => null,
            'card' => 'border-green-200 bg-green-50/60',
            'iconBox' => 'bg-green-100 text-green-600',
            'labelClass' => 'text-green-600',
            'valueClass' => 'text-green-600',
            'paths' => ['M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3'],
        ],
        [
            'label' => 'QR',
            'value' => $money($economic['qr']),
            'subtitle' => null,
            'card' => 'border-blue-200 bg-blue-50/60',
            'iconBox' => 'bg-blue-100 text-blue-600',
            'labelClass' => 'text-blue-600',
            'valueClass' => 'text-blue-600',
            'paths' => [
                'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z',
                'M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z',
            ],
        ],
        [
            'label' => 'Fiado',
            'value' => $money($economic['debt']),
            'subtitle' => null,
            'card' => 'border-amber-200 bg-amber-50/60',
            'iconBox' => 'bg-amber-100 text-amber-600',
            'labelClass' => 'text-amber-600',
            'valueClass' => 'text-amber-600',
            'paths' => ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ],
        [
            'label' => 'Ventas',
            'value' => $qty($economic['sales_count']),
            'subtitle' => 'Ticket prom. '.$money($economic['avg_ticket']),
            'card' => 'border-gray-200 bg-white',
            'iconBox' => 'bg-maxxi-navy/10 text-maxxi-navy',
            'labelClass' => 'text-gray-400',
            'valueClass' => 'text-gray-800',
            'paths' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ],
        [
            'label' => 'Ganancia bruta',
            'value' => $money($economic['profit']),
            'subtitle' => 'Margen '.number_format($economic['margin'], 1, '.', ',').'%',
            'card' => 'border-emerald-200 bg-emerald-50/60',
            'iconBox' => 'bg-emerald-100 text-emerald-600',
            'labelClass' => 'text-emerald-600',
            'valueClass' => 'text-emerald-700',
            'paths' => ['M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
        ],
    ];
@endphp

<x-layouts.sistema title="Dashboard">
    {{-- Encabezado de página + acciones principales --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Dashboard</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Hola, {{ auth()->user()->name }}. Resumen de la operación del {{ $today->format('d/m/Y') }}.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @can('manage-sale-notes')
                <a href="{{ route('sale-notes.create') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Nueva nota
                </a>
            @endcan
            <a href="{{ route('sales.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nueva venta
            </a>
        </div>
    </div>

    {{-- 1. Resumen de hoy --}}
    <section aria-label="Resumen de hoy">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($kpis as $kpi)
                <div class="rounded-xl border {{ $kpi['card'] }} p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="mb-0 text-xs font-medium uppercase tracking-wide {{ $kpi['labelClass'] }}">{{ $kpi['label'] }}</p>
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $kpi['iconBox'] }}" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                @foreach ($kpi['paths'] as $path)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                                @endforeach
                            </svg>
                        </span>
                    </div>
                    <p class="mb-0 mt-2 text-xl font-bold tabular-nums {{ $kpi['valueClass'] }}">{{ $kpi['value'] }}</p>
                    @if ($kpi['subtitle'])
                        <p class="mb-0 mt-0.5 text-xs text-gray-500">{{ $kpi['subtitle'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- 2. Tendencia de la semana + inventario --}}
    <section class="mt-8 grid gap-4 lg:grid-cols-3">
        {{-- Últimos 7 días --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </span>
                    <h2 class="mb-0 text-base font-semibold text-maxxi-navy">Últimos 7 días</h2>
                </div>
                <p class="mb-0 text-sm text-gray-500">Total <span class="font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($trendTotal) }}</span></p>
            </div>

            @if ($trendTotal <= 0)
                <div class="rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center">
                    <p class="mb-0 text-sm text-gray-500">Sin ventas en los últimos 7 días.</p>
                </div>
            @else
                <div class="flex h-36 items-end gap-2 border-b border-gray-100 sm:gap-3">
                    @foreach ($trend as $point)
                        @if ($point['net'] > 0)
                            @php $height = max(6, (int) round($point['net'] / $trendMax * 100)); @endphp
                            <div class="flex h-full flex-1 items-end">
                                <div class="w-full rounded-t-md bg-maxxi-orange/80 transition hover:bg-maxxi-orange"
                                     style="height: {{ $height }}%"
                                     title="{{ $point['date']->format('d/m/Y') }}: {{ $money($point['net']) }} ({{ $qty($point['count']) }} ventas)"></div>
                            </div>
                        @else
                            <div class="h-full flex-1" title="{{ $point['date']->format('d/m/Y') }}: sin ventas"></div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-2 flex gap-2 sm:gap-3">
                    @foreach ($trend as $point)
                        <div class="flex-1 text-center">
                            <p class="mb-0 text-[11px] font-medium text-gray-500">{{ $dias[$point['date']->dayOfWeekIso] }} {{ $point['date']->format('d') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Inventario --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </span>
                <h2 class="mb-0 text-base font-semibold text-maxxi-navy">Inventario</h2>
            </div>

            <ul class="mb-0 list-none space-y-3 p-0">
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-gray-600">Productos activos</span>
                    <span class="font-semibold tabular-nums text-gray-800">{{ $qty($inventory['products']) }}</span>
                </li>
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-gray-600">Presentaciones activas</span>
                    <span class="font-semibold tabular-nums text-gray-800">{{ $qty($inventory['presentations']) }}</span>
                </li>
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-gray-600">Unidades activas</span>
                    <span class="font-semibold tabular-nums text-gray-800">{{ $qty($inventory['units']) }}</span>
                </li>
                <li class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm">
                    <span class="text-gray-600">Productos agotados</span>
                    <span class="font-semibold tabular-nums {{ $inventory['outOfStock'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $qty($inventory['outOfStock']) }}</span>
                </li>
            </ul>

            @if ($outOfStock->isNotEmpty())
                <p class="mb-0 mt-3 truncate text-xs text-gray-400" title="{{ $outOfStock->pluck('name')->implode(', ') }}">
                    {{ $outOfStock->pluck('name')->implode(', ') }}
                </p>
            @endif

            <a href="{{ route('products.index') }}" class="{{ $botones['secundario'] }} mt-4 w-full">Ver productos</a>
        </div>
    </section>

    {{-- 3. Top productos + ventas recientes --}}
    <section class="mt-8 grid gap-4 lg:grid-cols-3">
        {{-- Top productos de hoy --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </span>
                    <h2 class="mb-0 text-base font-semibold text-maxxi-navy">Top productos de hoy</h2>
                </div>
            </div>

            @if ($topProducts->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center">
                    <p class="mb-0 text-sm text-gray-500">Sin productos vendidos hoy.</p>
                </div>
            @else
                <ul class="mb-0 list-none space-y-3 p-0">
                    @foreach ($topProducts as $row)
                        <li class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-maxxi-navy/10 text-xs font-semibold text-maxxi-navy">{{ $loop->iteration }}</span>
                                <span class="truncate text-sm text-gray-700">{{ $row->product_name }}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="mb-0 text-sm font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($row->revenue) }}</p>
                                <p class="mb-0 text-[11px] text-gray-500">
                                    {{ $qty($row->quantity) }}@if ($row->unit_symbol) {{ $row->unit_symbol }}@endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Ventas recientes --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </span>
                    <h2 class="mb-0 text-base font-semibold text-maxxi-navy">Ventas recientes</h2>
                </div>
                <a href="{{ route('sales.index') }}" class="text-sm font-medium text-maxxi-orange hover:underline">Ver todas</a>
            </div>

            @if ($recentSales->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center">
                    <p class="mb-0 text-sm text-gray-500">Aún no hay ventas registradas.</p>
                </div>
            @else
                {{-- Tabla (escritorio) --}}
                <div class="hidden overflow-hidden rounded-lg border border-gray-200 md:block">
                    <table class="mb-0 w-full border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide">
                                <th class="bg-maxxi-navy px-3 py-2 font-semibold text-white">Fecha</th>
                                <th class="bg-maxxi-navy px-3 py-2 font-semibold text-white">Cliente</th>
                                <th class="bg-maxxi-navy px-3 py-2 font-semibold text-white">Cajero</th>
                                <th class="bg-maxxi-navy px-3 py-2 text-right font-semibold text-white">Total</th>
                                <th class="bg-maxxi-navy px-3 py-2 text-right font-semibold text-white">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSales as $sale)
                                <tr data-href="{{ route('sales.show', $sale) }}" class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                    <td class="whitespace-nowrap px-3 py-2 text-gray-600">{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 font-medium text-gray-800">{{ $sale->customer_name ?: '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $sale->user?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($sale->total) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        @if ($sale->status === 'pagada')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($recentSales as $sale)
                        <a href="{{ route('sales.show', $sale) }}" class="block rounded-lg border border-gray-200 p-3 transition hover:border-maxxi-orange/40 hover:shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="mb-0 truncate font-medium text-gray-800">{{ $sale->customer_name ?: 'Venta #'.$sale->id }}</p>
                                @if ($sale->status === 'pagada')
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                                @else
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-500">{{ $sale->sold_at?->format('d/m/Y H:i') }} · {{ $sale->user?->name ?? '—' }}</span>
                                <span class="shrink-0 font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($sale->total) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 4. Notas de venta pendientes --}}
    @if ($pendingNotesCount > 0)
        <section class="mt-8" aria-labelledby="dashboard-notes-title">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <h2 id="dashboard-notes-title" class="mb-0 text-base font-semibold text-maxxi-navy">Notas de venta pendientes</h2>
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">{{ $qty($pendingNotesCount) }}</span>
                @can('manage-sale-notes')
                    <a href="{{ route('sale-notes.index') }}" class="ml-auto text-sm font-medium text-maxxi-orange hover:underline">Ver todas</a>
                @endcan
            </div>

            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Cliente</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Teléfono</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Programada</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Total</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nextDeliveries as $note)
                            <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $note->customer_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $note->customer_phone ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $note->scheduled_for?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($note->total) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('sale-notes.show', $note) }}" class="inline-flex items-center justify-center rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-xs font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($nextDeliveries as $note)
                    <a href="{{ route('sale-notes.show', $note) }}" class="block rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40">
                        <div class="flex items-center justify-between gap-3">
                            <p class="mb-0 font-semibold text-gray-800">{{ $note->customer_name ?: '—' }}</p>
                            <span class="shrink-0 font-semibold tabular-nums text-maxxi-orange-dark">{{ $money($note->total) }}</span>
                        </div>
                        <p class="mb-0 mt-1 text-sm text-gray-500">
                            {{ $note->customer_phone ?: 'Sin teléfono' }}
                            @if ($note->scheduled_for)
                                · Programada {{ $note->scheduled_for->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.sistema>
