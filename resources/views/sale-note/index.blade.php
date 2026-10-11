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

    @if ($saleNotes->isEmpty())
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
    @else
        {{-- Filtros --}}
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </span>
                <h2 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h2>
            </div>

            {{-- Buscador (filtra en cliente) --}}
            <div class="mb-4">
                <label for="sale-note-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input type="search" id="sale-note-filter-search" autocomplete="off"
                           placeholder="Buscar por cliente, teléfono o usuario…"
                           class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="sale-note-filter-date-from" class="mb-1 block text-sm font-medium text-gray-700">Fecha desde</label>
                    <input type="date" id="sale-note-filter-date-from" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
                <div>
                    <label for="sale-note-filter-date-to" class="mb-1 block text-sm font-medium text-gray-700">Fecha hasta</label>
                    <input type="date" id="sale-note-filter-date-to" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
                <div>
                    <label for="sale-note-filter-status" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                    <select id="sale-note-filter-status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange">
                        <option value="pendiente">Pendientes</option>
                        <option value="entregada">Entregadas</option>
                        <option value="anulada">Anuladas</option>
                        <option value="todas" selected>Todas</option>
                    </select>
                </div>
                <div class="flex flex-col justify-end gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-sale-note-range="today" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Hoy</button>
                        <button type="button" data-sale-note-range="week" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Esta semana</button>
                        <button type="button" data-sale-note-range="month" class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Este mes</button>
                        <button type="button" data-sale-note-clear class="{{ $botones['secundario'] }} px-2.5 py-1.5 text-xs sm:text-sm">Limpiar</button>
                    </div>
                </div>
            </div>
        </div>

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
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-800" id="sum-count">0</p>
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
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-orange-dark" id="sum-total">Bs. 0.0</p>
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
                <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-blue-600" id="sum-pending">0</p>
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

        {{-- Sin resultados para el filtro --}}
        <div id="sale-note-filter-empty" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-maxxi-orange/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="mb-0 mt-3 text-sm text-gray-500">No hay notas que coincidan con los filtros.</p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fromInput = document.getElementById('sale-note-filter-date-from');
                const toInput = document.getElementById('sale-note-filter-date-to');
                const statusSelect = document.getElementById('sale-note-filter-status');
                const searchInput = document.getElementById('sale-note-filter-search');
                const rangeButtons = document.querySelectorAll('[data-sale-note-range]');
                const clearButton = document.querySelectorAll('[data-sale-note-clear]');
                const items = document.querySelectorAll('[data-sale-note-item]');
                const list = document.getElementById('sale-note-list');
                const filterEmpty = document.getElementById('sale-note-filter-empty');
                const sumCount = document.getElementById('sum-count');
                const sumTotal = document.getElementById('sum-total');
                const sumPending = document.getElementById('sum-pending');

                function toYmd(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                function fmt(n) {
                    return 'Bs. ' + (Math.round(n * 10) / 10).toFixed(1);
                }

                function applyFilters() {
                    const from = fromInput?.value || '';
                    const to = toInput?.value || '';
                    const status = statusSelect?.value || 'todas';
                    const query = (searchInput?.value || '').trim().toLowerCase();
                    let count = 0;
                    let total = 0;
                    let pending = 0;
                    const seen = new Set();

                    items.forEach((item) => {
                        const itemDate = item.dataset.notedAt || '';
                        const itemStatus = item.dataset.status || '';

                        let okDate = true;
                        if (from && itemDate < from) okDate = false;
                        if (to && itemDate > to) okDate = false;

                        let okStatus = true;
                        if (status !== 'todas' && itemStatus !== status) okStatus = false;

                        let okSearch = true;
                        if (query && !(item.dataset.search || '').toLowerCase().includes(query)) okSearch = false;

                        const show = okDate && okStatus && okSearch;
                        item.classList.toggle('hidden', !show);

                        // Cada nota se renderiza dos veces (tabla y tarjeta);
                        // se agrega al resumen una sola vez por id.
                        const noteId = item.dataset.saleNoteId || '';
                        if (show && !seen.has(noteId)) {
                            seen.add(noteId);
                            count++;
                            total += parseFloat(item.dataset.total || 0);
                            if (itemStatus === 'pendiente') pending++;
                        }
                    });

                    if (sumCount) sumCount.textContent = count;
                    if (sumTotal) sumTotal.textContent = fmt(total);
                    if (sumPending) sumPending.textContent = pending;

                    list?.classList.toggle('hidden', count === 0);
                    filterEmpty?.classList.toggle('hidden', count > 0);
                }

                function setRange(type) {
                    const today = new Date();
                    let from, to;
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
                }

                fromInput?.addEventListener('input', applyFilters);
                toInput?.addEventListener('input', applyFilters);
                statusSelect?.addEventListener('change', applyFilters);
                searchInput?.addEventListener('input', applyFilters);

                rangeButtons.forEach((btn) => {
                    btn.addEventListener('click', () => setRange(btn.dataset.saleNoteRange));
                });

                clearButton.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        if (fromInput) fromInput.value = '';
                        if (toInput) toInput.value = '';
                        if (searchInput) searchInput.value = '';
                        if (statusSelect) statusSelect.value = 'todas';
                        applyFilters();
                    });
                });

                applyFilters();
            });
        </script>
    @endif
</x-layouts.sistema>
