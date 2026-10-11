@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        'peligro-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'exito-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
    ];
@endphp

<x-layouts.sistema title="Unidades">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Unidades</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Unidades de medida disponibles en el sistema.</p>
        </div>

        <a href="{{ route('units.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nueva unidad
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

    @if ($units->isEmpty())
        {{-- Estado vacío --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <p class="mb-1 mt-3 font-medium text-gray-700">Aún no hay unidades registradas</p>
            <p class="mb-0 text-sm text-gray-500">Crea la primera unidad de medida para empezar.</p>
            <a href="{{ route('units.create') }}" class="{{ $botones['primario'] }} mt-4">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nueva unidad
            </a>
        </div>
    @else
        {{-- Filtros: búsqueda libre + estado --}}
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
                <label for="unit-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input type="search" id="unit-filter-search" autocomplete="off"
                           placeholder="Buscar por nombre, símbolo o estado…"
                           class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                {{-- Filtro por estado (píldora segmentada) --}}
                <div class="inline-flex items-center self-start rounded-full border border-gray-200 bg-gray-100 p-1" role="group" aria-label="Filtrar unidades por estado">
                    <button type="button" data-unit-filter="active" aria-pressed="true"
                            class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                        Activas
                        <span class="rounded-full bg-green-100 px-1.5 text-xs font-semibold text-green-700">{{ $units->where('active', true)->count() }}</span>
                    </button>
                    <button type="button" data-unit-filter="inactive" aria-pressed="false"
                            class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
                        Inactivas
                        <span class="rounded-full bg-gray-200 px-1.5 text-xs font-semibold text-gray-600">{{ $units->where('active', false)->count() }}</span>
                    </button>
                </div>

                <button type="button" data-unit-clear class="{{ $botones['secundario'] }} self-start sm:self-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </button>
            </div>
        </div>

        <div id="unit-list">
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Nombre</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Símbolo</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr data-href="{{ route('units.show', $unit) }}"
                                data-unit-item data-active="{{ $unit->active ? '1' : '0' }}"
                                data-search="{{ trim(($unit->name ?? '') . ' ' . ($unit->symbol ?? '') . ' ' . ($unit->active ? 'activa' : 'inactiva')) }}"
                                class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $unit->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $unit->symbol }}</td>
                                <td class="px-4 py-3">
                                    @if ($unit->active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                                    @endif
                                </td>
                                <td data-href-ignore class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('units.show', $unit) }}" class="{{ $botones['secundario'] }}">Ver</a>
                                        <a href="{{ route('units.edit', $unit) }}" class="{{ $botones['marino'] }}">Editar</a>

                                        @if ($unit->active)
                                            <form action="{{ route('units.destroy', $unit) }}" method="POST"
                                                  data-unit-action="deactivate" data-unit-name="{{ $unit->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                            </form>
                                        @else
                                            <form action="{{ route('units.restore', $unit) }}" method="POST"
                                                  data-unit-action="restore" data-unit-name="{{ $unit->name }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                                            </form>
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
                @foreach ($units as $unit)
                    <div data-href="{{ route('units.show', $unit) }}"
                         data-unit-item data-active="{{ $unit->active ? '1' : '0' }}"
                         data-search="{{ trim(($unit->name ?? '') . ' ' . ($unit->symbol ?? '') . ' ' . ($unit->active ? 'activa' : 'inactiva')) }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="mb-0 font-medium text-gray-800">{{ $unit->name }}</p>
                                <p class="mb-0 mt-1 text-sm text-gray-500">Símbolo: {{ $unit->symbol }}</p>
                            </div>

                            @if ($unit->active)
                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                            @else
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
                            @endif
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('units.show', $unit) }}" class="{{ $botones['secundario'] }}">Ver</a>
                            <a href="{{ route('units.edit', $unit) }}" class="{{ $botones['marino'] }}">Editar</a>

                            @if ($unit->active)
                                <form action="{{ route('units.destroy', $unit) }}" method="POST"
                                      data-unit-action="deactivate" data-unit-name="{{ $unit->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
                                </form>
                            @else
                                <form action="{{ route('units.restore', $unit) }}" method="POST"
                                      data-unit-action="restore" data-unit-name="{{ $unit->name }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="{{ $botones['exito'] }}">Reactivar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sin resultados para el filtro --}}
        <div id="unit-filter-empty" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="mb-0 mt-3 text-sm text-gray-500">No hay unidades que coincidan con los filtros.</p>
        </div>
    @endif

    {{-- Modales: confirmar baja y reactivación --}}
    <x-modal name="confirm-unit-deactivate" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Dar de baja esta unidad?</h2>
                    <p id="confirm-unit-deactivate-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Dejará de estar disponible, pero podrás reactivarla después.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-unit-deactivate')">
                    Cancelar
                </button>
                <button type="button" id="confirm-unit-deactivate-button" class="{{ $botones['peligro-solido'] }}">
                    Sí, dar de baja
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-unit-restore" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Reactivar esta unidad?</h2>
                    <p id="confirm-unit-restore-summary" class="mb-0 text-sm text-gray-600"></p>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Volverá a estar disponible en el sistema.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-unit-restore')">
                    Cancelar
                </button>
                <button type="button" id="confirm-unit-restore-button" class="{{ $botones['exito-solido'] }}">
                    Sí, reactivar
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        const unitFilterButtons = document.querySelectorAll('[data-unit-filter]');
        const unitItems = document.querySelectorAll('[data-unit-item]');
        const unitList = document.getElementById('unit-list');
        const unitFilterEmpty = document.getElementById('unit-filter-empty');
        const unitSearchInput = document.getElementById('unit-filter-search');
        const unitClearButton = document.querySelector('[data-unit-clear]');

        // Estado seleccionado en la píldora; por defecto, activas.
        let unitActiveFilter = 'active';

        const applyUnitFilter = () => {
            const query = (unitSearchInput?.value || '').trim().toLowerCase();
            let visible = 0;

            unitItems.forEach((item) => {
                const isActive = item.dataset.active === '1';
                const matchesState = unitActiveFilter === 'active' ? isActive : !isActive;

                let matchesSearch = true;
                if (query && !(item.dataset.search || '').toLowerCase().includes(query)) {
                    matchesSearch = false;
                }

                // Un ítem se muestra solo si cumple el estado Y la búsqueda.
                const show = matchesState && matchesSearch;
                item.classList.toggle('hidden', !show);
                if (show) {
                    visible++;
                }
            });

            // Oculta la lista completa (tabla + tarjetas) si el filtro no arroja resultados.
            unitList?.classList.toggle('hidden', visible === 0);
            unitFilterEmpty?.classList.toggle('hidden', visible > 0);

            unitFilterButtons.forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.unitFilter === unitActiveFilter ? 'true' : 'false');
            });
        };

        unitFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                unitActiveFilter = button.dataset.unitFilter;
                applyUnitFilter();
            });
        });

        unitSearchInput?.addEventListener('input', applyUnitFilter);

        unitClearButton?.addEventListener('click', () => {
            if (unitSearchInput) {
                unitSearchInput.value = '';
            }
            unitActiveFilter = 'active';
            applyUnitFilter();
        });

        applyUnitFilter();
    </script>

    {{-- Confirmación de baja/reactivación --}}
    <script>
        (function () {
            var actionForms = document.querySelectorAll('[data-unit-action]');
            var deactivateSummary = document.getElementById('confirm-unit-deactivate-summary');
            var restoreSummary = document.getElementById('confirm-unit-restore-summary');
            var deactivateButton = document.getElementById('confirm-unit-deactivate-button');
            var restoreButton = document.getElementById('confirm-unit-restore-button');
            var pendingForm = null;
            var bypassConfirm = false;

            actionForms.forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (bypassConfirm) { return; }

                    event.preventDefault();
                    pendingForm = form;

                    var name = form.dataset.unitName || '';

                    if (form.dataset.unitAction === 'deactivate') {
                        if (deactivateSummary) {
                            deactivateSummary.textContent = 'Se dará de baja la unidad «' + name + '».';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-unit-deactivate' }));
                    } else {
                        if (restoreSummary) {
                            restoreSummary.textContent = 'Se reactivará la unidad «' + name + '».';
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-unit-restore' }));
                    }
                });
            });

            function submitPending(modalName) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));

                if (!pendingForm) { return; }

                bypassConfirm = true;

                if (typeof pendingForm.requestSubmit === 'function') {
                    pendingForm.requestSubmit();
                } else {
                    pendingForm.submit();
                }

                bypassConfirm = false;
                pendingForm = null;
            }

            deactivateButton?.addEventListener('click', function () { submitPending('confirm-unit-deactivate'); });
            restoreButton?.addEventListener('click', function () { submitPending('confirm-unit-restore'); });
        })();
    </script>
</x-layouts.sistema>
