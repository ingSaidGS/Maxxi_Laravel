@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];

    // Estado de la nota: etiqueta + clases del badge (maxxi-ui §Badges).
    $statusMeta = [
        'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-blue-100 text-blue-700'],
        'entregada' => ['label' => 'Entregada', 'class' => 'bg-green-100 text-green-700'],
        'anulada' => ['label' => 'Anulada', 'class' => 'bg-gray-200 text-gray-600'],
    ];

    // Detectar filtros activos (el controller ya normalizó los parámetros).
    $hasFilters = ($filters['date_from'] ?? null) !== null
        || ($filters['date_to'] ?? null) !== null
        || ($filters['status'] ?? null) !== null
        || trim($filters['search'] ?? '') !== '';
@endphp

<x-layouts.sistema title="Notas de venta">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Notas de venta</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Pedidos tomados que se convierten en venta al entregarse.</p>
        </div>

        <a href="{{ route('sale-notes.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nueva nota
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

    {{-- Filtros de búsqueda: siempre visibles, aunque no haya notas --}}
    <form method="GET" action="{{ route('sale-notes.index') }}" id="sale-note-filters-form" class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <input type="hidden" name="per_page" value="{{ (int) request('per_page', 15) }}">

        <div class="mb-4 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </span>
            <h2 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h2>
            @if ($hasFilters)
                <a href="{{ route('sale-notes.index') }}" class="ml-auto text-xs font-medium text-maxxi-orange hover:underline">Limpiar filtros</a>
            @endif
        </div>

        {{-- Buscador (consulta al servidor: alcanza notas de cualquier fecha) --}}
        <div class="mb-4">
            <label for="sale-note-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input type="search" id="sale-note-filter-search" name="search" value="{{ $filters['search'] }}"
                           autocomplete="off" placeholder="Buscar por cliente, teléfono o usuario…"
                           class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
                <button type="submit" class="{{ $botones['marino'] }} shrink-0">Buscar</button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="sale-note-filter-date-from" class="mb-1 block text-sm font-medium text-gray-700">Fecha desde</label>
                <input type="date" id="sale-note-filter-date-from" name="date_from" value="{{ $filters['date_from'] }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>
            <div>
                <label for="sale-note-filter-date-to" class="mb-1 block text-sm font-medium text-gray-700">Fecha hasta</label>
                <input type="date" id="sale-note-filter-date-to" name="date_to" value="{{ $filters['date_to'] }}"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
            </div>
            <div>
                <label for="sale-note-filter-status" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                <select id="sale-note-filter-status" name="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                    <option value="pendiente" @selected($filters['status'] === 'pendiente')>Pendientes</option>
                    <option value="entregada" @selected($filters['status'] === 'entregada')>Entregadas</option>
                    <option value="anulada" @selected($filters['status'] === 'anulada')>Anuladas</option>
                    <option value="" @selected(! $filters['status'])>Todas</option>
                </select>
            </div>
            <div class="flex flex-col justify-end gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-sale-note-range="today" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Hoy</button>
                    <button type="button" data-sale-note-range="week" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Esta semana</button>
                    <button type="button" data-sale-note-range="month" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Este mes</button>
                    <a href="{{ route('sale-notes.index') }}" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Limpiar</a>
                </div>
            </div>
        </div>
    </form>

    @if ($saleNotes->isEmpty())
        @if ($hasFilters)
            {{-- Estado vacío: hay filtros activos y no coinciden con ninguna nota --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">No hay notas que coincidan con los filtros</p>
                <p class="mb-0 text-sm text-gray-500">Ajusta el rango de fechas, el estado o el término de búsqueda.</p>
                <a href="{{ route('sale-notes.index') }}" class="{{ $botones['secundario'] }} mt-4">Limpiar filtros</a>
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mb-1 mt-3 font-medium text-gray-700">Aún no hay notas de venta</p>
                <p class="mb-0 text-sm text-gray-500">Registra la primera nota para empezar.</p>
                <a href="{{ route('sale-notes.create') }}" class="{{ $botones['primario'] }} mt-4">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Nueva nota
                </a>
            </div>
        @endif
    @else
        {{-- Resumen filtrado --}}
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

            <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-blue-600">Por entregar</p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H18.75m-2.25 3.75H14.25m4.5-3.75V6.75A2.25 2.25 0 0016.5 4.5h-6A2.25 2.25 0 008.25 6.75v4.5m0 0H3.375A1.125 1.125 0 002.25 12.375v1.5c0 .621.504 1.125 1.125 1.125h1.5" />
                        </svg>
                    </span>
                </div>
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-blue-600" id="sum-pending">{{ $summary['pending'] }}</p>
            </div>
        </div>

        <div id="sale-note-list">
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Cliente</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Teléfono</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Fecha</th>
                            <th class="bg-maxxi-navy whitespace-nowrap px-4 py-3 font-semibold text-white">Entrega</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Total</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Usuario</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($saleNotes as $saleNote)
                            @php($meta = $statusMeta[$saleNote->status] ?? ['label' => ucfirst($saleNote->status), 'class' => 'bg-gray-100 text-gray-600'])
                            <tr data-href="{{ route('sale-notes.show', $saleNote) }}"
                                data-sale-note-item data-sale-note-id="{{ $saleNote->id }}" data-status="{{ $saleNote->status }}"
                                data-noted-at="{{ $saleNote->noted_at?->format('Y-m-d') }}"
                                data-search="{{ trim(($saleNote->customer_name ?? '') . ' ' . ($saleNote->customer_phone ?? '') . ' ' . ($saleNote->user?->name ?? '')) }}"
                                data-total="{{ (float) $saleNote->total }}"
                                class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $saleNote->customer_name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-600">{{ $saleNote->customer_phone ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-600">{{ $saleNote->noted_at?->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-600">{{ $saleNote->scheduled_for?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-maxxi-orange-dark">Bs. {{ $saleNote->total }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $saleNote->user?->name ?? '—' }}</td>
                                <td data-href-ignore class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('sale-notes.show', $saleNote) }}" class="{{ $botones['secundario'] }}">Ver</a>
                                        @if ($saleNote->status === 'pendiente')
                                            <a href="{{ route('sale-notes.edit', $saleNote) }}" class="{{ $botones['marino'] }}">Editar</a>
                                        @elseif ($saleNote->status === 'entregada' && $saleNote->sale)
                                            <a href="{{ route('sales.show', $saleNote->sale) }}" class="{{ $botones['marino'] }}">Venta #{{ $saleNote->sale->id }}</a>
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
                @foreach ($saleNotes as $saleNote)
                    @php($meta = $statusMeta[$saleNote->status] ?? ['label' => ucfirst($saleNote->status), 'class' => 'bg-gray-100 text-gray-600'])
                    <div data-href="{{ route('sale-notes.show', $saleNote) }}"
                         data-sale-note-item data-sale-note-id="{{ $saleNote->id }}" data-status="{{ $saleNote->status }}"
                         data-noted-at="{{ $saleNote->noted_at?->format('Y-m-d') }}"
                         data-search="{{ trim(($saleNote->customer_name ?? '') . ' ' . ($saleNote->customer_phone ?? '') . ' ' . ($saleNote->user?->name ?? '')) }}"
                         data-total="{{ (float) $saleNote->total }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="mb-0 font-semibold text-gray-800">{{ $saleNote->customer_name ?: '—' }}</p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Teléfono: <span class="tabular-nums text-gray-800">{{ $saleNote->customer_phone ?: '—' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Fecha: <span class="tabular-nums text-gray-800">{{ $saleNote->noted_at?->format('d/m/Y H:i') }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Entrega: <span class="tabular-nums text-gray-800">{{ $saleNote->scheduled_for?->format('d/m/Y H:i') ?? '—' }}</span></p>
                                <p class="mb-0 mt-0.5 text-sm text-gray-500">Usuario: <span class="text-gray-800">{{ $saleNote->user?->name ?? '—' }}</span></p>
                            </div>

                            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </div>

                        <div class="mt-3 rounded-lg bg-maxxi-orange/10 px-3 py-2">
                            <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</p>
                            <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $saleNote->total }}</p>
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('sale-notes.show', $saleNote) }}" class="{{ $botones['secundario'] }}">Ver</a>
                            @if ($saleNote->status === 'pendiente')
                                <a href="{{ route('sale-notes.edit', $saleNote) }}" class="{{ $botones['marino'] }}">Editar</a>
                            @elseif ($saleNote->status === 'entregada' && $saleNote->sale)
                                <a href="{{ route('sales.show', $saleNote->sale) }}" class="{{ $botones['marino'] }}">Venta #{{ $saleNote->sale->id }}</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($saleNotes->hasPages() || $saleNotes->total() > 0)
            <div class="mt-4 flex flex-col items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row">
                <div class="flex flex-col items-center gap-2 sm:flex-row sm:gap-3">
                    <p class="mb-0 text-sm text-gray-500">
                        Mostrando {{ $saleNotes->firstItem() }}–{{ $saleNotes->lastItem() }} de {{ $saleNotes->total() }} notas
                    </p>
                    <form method="GET" action="{{ route('sale-notes.index') }}" class="flex items-center gap-2 text-sm text-gray-600">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="sale-note-per-page" class="font-medium text-gray-700">Mostrar</label>
                        <select id="sale-note-per-page" name="per_page" onchange="this.form.submit()"
                                class="rounded-lg border-gray-300 py-1 pl-2 pr-8 text-sm shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                            @foreach ([10, 15, 25, 50, 100] as $option)
                                <option value="{{ $option }}" @selected((int) request('per_page', 15) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span>por página</span>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    {{ $saleNotes->links() }}
                </div>
            </div>
        @endif
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('sale-note-filters-form');
            if (!form) return;

            const fromInput = document.getElementById('sale-note-filter-date-from');
            const toInput = document.getElementById('sale-note-filter-date-to');
            const statusSelect = document.getElementById('sale-note-filter-status');
            const rangeButtons = document.querySelectorAll('[data-sale-note-range]');

            // Los filtros se aplican en el servidor (el resultado no depende
            // del HTML cargado). El submit vuelve siempre a la página 1; el
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
                    const type = btn.dataset.saleNoteRange;
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
</x-layouts.sistema>
