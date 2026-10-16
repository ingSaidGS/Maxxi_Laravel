@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'cobrar' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1',
    ];

    // Detectar filtros activos (el controller ya normalizó los parámetros).
    $hasFilters = ($filters['date_from'] ?? null) !== null
        || ($filters['date_to'] ?? null) !== null
        || ($filters['status'] ?? null) !== null
        || trim($filters['search'] ?? '') !== '';
@endphp

<x-layouts.sistema title="Ventas">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Ventas</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Ventas registradas en el sistema.</p>
        </div>

        <a href="{{ route('sales.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nueva venta
        </a>
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

    {{-- Filtros de búsqueda: siempre visibles, aunque hoy no haya ventas --}}
    <form method="GET" action="{{ route('sales.index') }}" id="sale-filters-form" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <input type="hidden" name="per_page" value="{{ (int) request('per_page', 15) }}">

        <div class="mb-4 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </span>
            <h2 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h2>
            @if ($hasFilters)
                <a href="{{ route('sales.index') }}" class="ml-auto text-xs font-medium text-maxxi-orange hover:underline">Limpiar filtros</a>
            @endif
        </div>

        {{-- Buscador (consulta al servidor: alcanza ventas de cualquier fecha) --}}
        <div class="mb-4">
            <label for="sale-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input type="search" id="sale-filter-search" name="search" value="{{ $filters['search'] }}"
                           autocomplete="off" placeholder="Buscar por cliente, teléfono o usuario…"
                           class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
                <button type="submit" class="{{ $botones['marino'] }} shrink-0">Buscar</button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="sale-filter-date-from" class="mb-1 block text-sm font-medium text-gray-700">Fecha desde</label>
                <input type="date" id="sale-filter-date-from" name="date_from" value="{{ $filters['date_from'] }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>
            <div>
                <label for="sale-filter-date-to" class="mb-1 block text-sm font-medium text-gray-700">Fecha hasta</label>
                <input type="date" id="sale-filter-date-to" name="date_to" value="{{ $filters['date_to'] }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>
            <div>
                <label for="sale-filter-status" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                <select id="sale-filter-status" name="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                    <option value="" @selected(! $filters['status'])>Todos</option>
                    <option value="pagada" @selected($filters['status'] === 'pagada')>Pagadas</option>
                    <option value="fiada" @selected($filters['status'] === 'fiada')>Fiadas</option>
                </select>
            </div>
            <div class="flex flex-col justify-end gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-sale-range="today" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Hoy</button>
                    <button type="button" data-sale-range="week" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Esta semana</button>
                    <button type="button" data-sale-range="month" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Este mes</button>
                    <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Limpiar</a>
                </div>
            </div>
        </div>
    </form>

    @if ($sales->isEmpty())
        @if ($hasFilters)
            {{-- Estado vacío: hay filtros activos y no coinciden con ninguna venta --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">No hay ventas que coincidan con los filtros</p>
                <p class="mb-0 text-sm text-gray-500">Ajusta el rango de fechas, el estado o el término de búsqueda.</p>
                <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} mt-4">Limpiar filtros</a>
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">Aún no hay ventas registradas</p>
                <p class="mb-0 text-sm text-gray-500">Registra la primera venta para empezar.</p>
                <a href="{{ route('sales.create') }}" class="{{ $botones['primario'] }} mt-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Nueva venta
                </a>
            </div>
        @endif
    @else
        {{-- Resumen filtrado --}}
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Ventas</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-800" id="sum-count">{{ $summary['count'] }}</p>
            </div>

            <div class="rounded-xl border border-maxxi-orange/30 bg-maxxi-orange/5 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-maxxi-orange-dark">Total</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-orange-dark" id="sum-total">Bs. {{ number_format($summary['total'], 1) }}</p>
            </div>

            <div class="rounded-xl border border-green-200 bg-green-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-green-600">Efectivo</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-green-600" id="sum-cash">Bs. {{ number_format($summary['cash'], 1) }}</p>
            </div>

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
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-blue-600" id="sum-qr">Bs. {{ number_format($summary['qr'], 1) }}</p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-amber-600">Fiado</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-amber-600" id="sum-debt">Bs. {{ number_format($summary['debt'], 1) }}</p>
            </div>
        </div>

        <div id="sale-list">
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Cliente</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Teléfono</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Fecha</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Total</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Efectivo</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">QR</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Fiado</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Usuario</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr data-href="{{ route('sales.show', $sale) }}"
                                data-sale-item data-sale-id="{{ $sale->id }}" data-status="{{ $sale->status }}" data-sold-at="{{ $sale->sold_at?->format('Y-m-d') }}"
                                data-search="{{ trim(($sale->customer_name ?? '') . ' ' . ($sale->customer_phone ?? '') . ' ' . ($sale->user?->name ?? '')) }}"
                                data-total="{{ (float)$sale->total }}" data-cash="{{ (float)$sale->cash }}" data-qr="{{ (float)$sale->qr }}" data-debt="{{ (float)$sale->debt }}"
                                class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $sale->customer_name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-600">{{ $sale->customer_phone ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-600">{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-maxxi-orange-dark">Bs. {{ $sale->total }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-green-600">Bs. {{ $sale->cash }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-blue-600">Bs. {{ $sale->qr }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-amber-600">Bs. {{ $sale->debt }}</td>
                                <td class="px-4 py-3">
                                    @if ($sale->status === 'pagada')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $sale->user?->name ?? '—' }}</td>
                                <td data-href-ignore class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('sales.show', $sale) }}" class="{{ $botones['secundario'] }}">Ver</a>
                                        @if ($sale->status === 'fiada')
                                            <button type="button" data-cobrar
                                                    data-action="{{ route('sales.cobrar', $sale) }}"
                                                    data-sale-id="{{ $sale->id }}"
                                                    data-customer="{{ $sale->customer_name ?: '—' }}"
                                                    data-total="{{ (float) $sale->total }}"
                                                    data-cash="{{ (float) $sale->cash }}"
                                                    data-qr="{{ (float) $sale->qr }}"
                                                    data-debt="{{ (float) $sale->debt }}"
                                                    class="{{ $botones['cobrar'] }}">Cobrar</button>
                                        @endif
                                        <a href="{{ route('sales.edit', $sale) }}" class="{{ $botones['marino'] }}">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($sales as $sale)
                    <div data-href="{{ route('sales.show', $sale) }}"
                         data-sale-item data-sale-id="{{ $sale->id }}" data-status="{{ $sale->status }}" data-sold-at="{{ $sale->sold_at?->format('Y-m-d') }}"
                         data-search="{{ trim(($sale->customer_name ?? '') . ' ' . ($sale->customer_phone ?? '') . ' ' . ($sale->user?->name ?? '')) }}"
                         data-total="{{ (float)$sale->total }}" data-cash="{{ (float)$sale->cash }}" data-qr="{{ (float)$sale->qr }}" data-debt="{{ (float)$sale->debt }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="mb-0 font-semibold text-gray-800">{{ $sale->customer_name ?: '—' }}</p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Teléfono: <span class="tabular-nums text-gray-800">{{ $sale->customer_phone ?: '—' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Fecha: <span class="tabular-nums text-gray-800">{{ $sale->sold_at?->format('d/m/Y H:i') }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Usuario: <span class="text-gray-800">{{ $sale->user?->name ?? '—' }}</span></p>
                            </div>

                            @if ($sale->status === 'pagada')
                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Pagada</span>
                            @else
                                <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Fiada</span>
                            @endif
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-maxxi-orange/10 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->total }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-green-600">Efectivo</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->cash }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-blue-600">QR</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->qr }}</p>
                            </div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2">
                                <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-amber-600">Fiado</p>
                                <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->debt }}</p>
                            </div>
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('sales.show', $sale) }}" class="{{ $botones['secundario'] }}">Ver</a>
                            @if ($sale->status === 'fiada')
                                <button type="button" data-cobrar
                                        data-action="{{ route('sales.cobrar', $sale) }}"
                                        data-sale-id="{{ $sale->id }}"
                                        data-customer="{{ $sale->customer_name ?: '—' }}"
                                        data-total="{{ (float) $sale->total }}"
                                        data-cash="{{ (float) $sale->cash }}"
                                        data-qr="{{ (float) $sale->qr }}"
                                        data-debt="{{ (float) $sale->debt }}"
                                        class="{{ $botones['cobrar'] }}">Cobrar</button>
                            @endif
                            <a href="{{ route('sales.edit', $sale) }}" class="{{ $botones['marino'] }}">Editar</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($sales->hasPages() || $sales->total() > 0)
            <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row">
                <div class="flex flex-col items-center gap-2 sm:flex-row sm:gap-3">
                    <p class="mb-0 text-sm text-gray-500">
                        Mostrando {{ $sales->firstItem() }}–{{ $sales->lastItem() }} de {{ $sales->total() }} ventas
                    </p>
                    <form method="GET" action="{{ route('sales.index') }}" class="flex items-center gap-2 text-sm text-gray-600">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="sale-per-page" class="font-medium text-gray-700">Mostrar</label>
                        <select id="sale-per-page" name="per_page" onchange="this.form.submit()"
                                class="rounded-lg border-gray-300 py-1 pl-2 pr-8 text-sm shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                            @foreach ([10, 15, 25, 50, 100] as $option)
                                <option value="{{ $option }}" @selected((int) request('per_page', 15) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span>por página</span>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    {{ $sales->links() }}
                </div>
            </div>
        @endif
    @endif

    {{-- Modal: registrar cobro de una venta fiada --}}
    <x-modal name="cobro" max-width="md" focusable>
        <form id="cobro-form" method="POST" action="#" class="p-6">
            @csrf
            @method('PATCH')

            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">Registrar cobro</h2>
                    <p class="mb-0 text-sm text-gray-600">
                        Venta <span id="cobro-sale-id" class="font-medium text-gray-800">#</span> · <span id="cobro-customer" class="text-gray-800">—</span>
                    </p>
                </div>
            </div>

            {{-- Resumen actual --}}
            <div class="mt-5 grid grid-cols-3 gap-3 rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total</p>
                    <p id="cobro-total" class="mb-0 mt-1 text-base font-semibold tabular-nums text-gray-800">Bs. 0.0</p>
                </div>
                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Pagado</p>
                    <p id="cobro-paid" class="mb-0 mt-1 text-base font-semibold tabular-nums text-gray-800">Bs. 0.0</p>
                </div>
                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fio pendiente</p>
                    <p id="cobro-debt" class="mb-0 mt-1 text-base font-semibold tabular-nums text-amber-600">Bs. 0.0</p>
                </div>
            </div>

            {{-- Montos recibidos --}}
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="received_cash" class="mb-1 block text-sm font-medium text-gray-700">Efectivo recibido (Bs.)</label>
                    <input type="number" id="received_cash" name="received_cash" step="0.1" min="0" inputmode="decimal" placeholder="Bs. 0.0"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange/40" />
                </div>
                <div>
                    <label for="received_qr" class="mb-1 block text-sm font-medium text-gray-700">QR recibido (Bs.)</label>
                    <input type="number" id="received_qr" name="received_qr" step="0.1" min="0" inputmode="decimal" placeholder="Bs. 0.0"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange/40" />
                </div>
            </div>

            {{-- Resultado del cobro --}}
            <p id="cobro-result" class="mt-4 mb-0 hidden rounded-lg border px-3 py-2 text-sm"></p>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'cobro')">
                    Cancelar
                </button>
                <button type="submit" class="{{ $botones['primario'] }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Registrar cobro
                </button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('sale-filters-form');
            if (!form) return;

            const fromInput = document.getElementById('sale-filter-date-from');
            const toInput = document.getElementById('sale-filter-date-to');
            const statusSelect = document.getElementById('sale-filter-status');
            const rangeButtons = document.querySelectorAll('[data-sale-range]');

            // Los filtros se aplican en el servidor (el resultado no depende de
            // la página actual). El submit vuelve siempre a la página 1; el
            // buscador se envía con Enter o el botón "Buscar".
            function applyFilters() {
                form.submit();
            }

            function toYmd(d) {
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            fromInput?.addEventListener('change', applyFilters);
            toInput?.addEventListener('change', applyFilters);
            statusSelect?.addEventListener('change', applyFilters);

            rangeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const today = new Date();
                    const type = btn.dataset.saleRange;
                    let from = '';
                    let to = '';

                    if (type === 'today') {
                        from = to = toYmd(today);
                    } else if (type === 'week') {
                        const day = today.getDay();
                        const monday = new Date(today);
                        monday.setDate(today.getDate() - (day === 0 ? 6 : day - 1));
                        from = toYmd(monday);
                        to = toYmd(today);
                    } else if (type === 'month') {
                        from = toYmd(new Date(today.getFullYear(), today.getMonth(), 1));
                        to = toYmd(today);
                    }

                    if (fromInput) fromInput.value = from;
                    if (toInput) toInput.value = to;

                    applyFilters();
                });
            });
        });
    </script>

    {{-- Lógica del modal de cobro (ventas fiadas) --}}
    <script>
        (function () {
            var form = document.getElementById('cobro-form');
            if (!form) return;

            var idEl = document.getElementById('cobro-sale-id');
            var customerEl = document.getElementById('cobro-customer');
            var totalEl = document.getElementById('cobro-total');
            var paidEl = document.getElementById('cobro-paid');
            var debtEl = document.getElementById('cobro-debt');
            var cashInput = document.getElementById('received_cash');
            var qrInput = document.getElementById('received_qr');
            var resultEl = document.getElementById('cobro-result');

            var current = { total: 0, cash: 0, qr: 0, debt: 0 };

            function money(n) {
                return 'Bs. ' + (Math.round(n * 10) / 10).toFixed(1);
            }

            function parseMoney(v) {
                var s = (v === undefined || v === null) ? '' : String(v).trim();
                if (s === '') return 0;
                var num = parseFloat(s.replace(',', '.'));
                return isNaN(num) ? 0 : num;
            }

            function renderPreview() {
                var received = parseMoney(cashInput.value) + parseMoney(qrInput.value);
                var paid = current.cash + current.qr + received;
                var fiada = paid < current.total - 0.05;
                var change = fiada ? 0 : Math.round(Math.max(0, paid - current.total) * 10) / 10;
                var newDebt = fiada ? Math.round(Math.max(0, current.total - paid) * 10) / 10 : 0;

                resultEl.classList.remove('hidden');

                if (received <= 0) {
                    resultEl.className = 'mt-4 mb-0 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600';
                    resultEl.textContent = 'Ingresa un monto en efectivo o QR para registrar el cobro.';
                    return;
                }

                if (fiada) {
                    resultEl.className = 'mt-4 mb-0 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800';
                    resultEl.textContent = 'Cobro parcial: la venta seguirá FIADA con un fio pendiente de ' + money(newDebt) + '.';
                } else {
                    resultEl.className = 'mt-4 mb-0 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800';
                    resultEl.textContent = 'La venta quedará PAGADA.' + (change > 0 ? ' Vuelto: ' + money(change) + '.' : '');
                }
            }

            document.querySelectorAll('[data-cobrar]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    current = {
                        total: parseFloat(btn.dataset.total || 0),
                        cash: parseFloat(btn.dataset.cash || 0),
                        qr: parseFloat(btn.dataset.qr || 0),
                        debt: parseFloat(btn.dataset.debt || 0),
                    };

                    form.setAttribute('action', btn.dataset.action);
                    idEl.textContent = '#' + (btn.dataset.saleId || '');
                    customerEl.textContent = btn.dataset.customer || '—';
                    totalEl.textContent = money(current.total);
                    paidEl.textContent = money(current.cash + current.qr);
                    debtEl.textContent = money(current.debt);

                    // Por defecto se propone cobrar todo el fio en efectivo; el cajero
                    // puede ajustar el monto o repartirlo con QR.
                    cashInput.value = current.debt > 0 ? (Math.round(current.debt * 10) / 10).toFixed(1) : '';
                    qrInput.value = '';
                    renderPreview();

                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'cobro' }));
                });
            });

            cashInput.addEventListener('input', renderPreview);
            qrInput.addEventListener('input', renderPreview);
        })();
    </script>
</x-layouts.sistema>
