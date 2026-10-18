@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Usuarios">
    {{-- Encabezado de página + acción principal --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Usuarios</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Usuarios del sistema y sus credenciales de acceso.</p>
        </div>

        <a href="{{ route('users.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nuevo usuario
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

    @if ($users->isEmpty())
        {{-- Estado vacío --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <p class="mb-1 mt-3 font-medium text-gray-700">Aún no hay usuarios registrados</p>
            <p class="mb-0 text-sm text-gray-500">Crea el primer usuario para dar acceso al sistema.</p>
            <a href="{{ route('users.create') }}" class="{{ $botones['primario'] }} mt-4">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nuevo usuario
            </a>
        </div>
    @else
        {{-- Filtros: búsqueda libre --}}
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </span>
                <h2 class="mb-0 text-sm font-semibold text-maxxi-navy">Filtros</h2>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="w-full sm:max-w-md">
                    <label for="user-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <input type="search" id="user-filter-search" autocomplete="off"
                               placeholder="Buscar por nombre, correo o rol…"
                               class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                    </div>
                </div>

                <button type="button" data-user-clear class="{{ $botones['secundario'] }} self-start sm:self-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </button>
            </div>
        </div>

        <div id="user-list">
            {{-- Tabla (escritorio) --}}
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <table class="mb-0 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide">
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Nombre</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Correo</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Rol</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                            <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Registrado</th>
                            <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr data-href="{{ route('users.show', $user) }}"
                                data-user-item
                                data-search="{{ trim(($user->name ?? '') . ' ' . ($user->email ?? '') . ' ' . ($user->tipo->value ?? '') . ' ' . ($user->tipo->label() ?? '') . ' ' . ($user->email_verified_at ? 'verificado' : 'sin verificar')) }}"
                                class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->isEncargado())
                                        <span class="inline-flex items-center rounded-full bg-maxxi-navy/10 px-2.5 py-0.5 text-xs font-medium text-maxxi-navy">Encargado</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Empleado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->email_verified_at)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Verificado</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Sin verificar</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                                <td data-href-ignore class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('users.show', $user) }}" class="{{ $botones['secundario'] }}">Ver</a>
                                        <a href="{{ route('users.edit', $user) }}" class="{{ $botones['marino'] }}">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="space-y-3 md:hidden">
                @foreach ($users as $user)
                    <div data-href="{{ route('users.show', $user) }}"
                         data-user-item
                         data-search="{{ trim(($user->name ?? '') . ' ' . ($user->email ?? '') . ' ' . ($user->tipo->value ?? '') . ' ' . ($user->tipo->label() ?? '') . ' ' . ($user->email_verified_at ? 'verificado' : 'sin verificar')) }}"
                         class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="mb-0 truncate font-medium text-gray-800">{{ $user->name }}</p>
                                <p class="mb-0 mt-1 truncate text-sm text-gray-500">{{ $user->email }}</p>
                                <p class="mb-0 mt-1 text-xs text-gray-400">Rol: {{ $user->tipo->label() }} · Registrado el {{ $user->created_at?->format('d/m/Y H:i') }}</p>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                @if ($user->isEncargado())
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-maxxi-navy/10 px-2.5 py-0.5 text-xs font-medium text-maxxi-navy">Encargado</span>
                                @else
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Empleado</span>
                                @endif
                                @if ($user->email_verified_at)
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Verificado</span>
                                @else
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Sin verificar</span>
                                @endif
                            </div>
                        </div>

                        <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('users.show', $user) }}" class="{{ $botones['secundario'] }}">Ver</a>
                            <a href="{{ route('users.edit', $user) }}" class="{{ $botones['marino'] }}">Editar</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sin resultados para el filtro --}}
        <div id="user-filter-empty" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="mb-0 mt-3 text-sm text-gray-500">No hay usuarios que coincidan con la búsqueda.</p>
        </div>
    @endif

    {{-- Filtro de búsqueda en cliente --}}
    <script>
        const userSearchInput = document.getElementById('user-filter-search');
        const userItems = document.querySelectorAll('[data-user-item]');
        const userList = document.getElementById('user-list');
        const userFilterEmpty = document.getElementById('user-filter-empty');
        const userClearButton = document.querySelector('[data-user-clear]');

        const applyUserFilter = () => {
            const query = (userSearchInput?.value || '').trim().toLowerCase();
            let visible = 0;

            userItems.forEach((item) => {
                const show = !query || (item.dataset.search || '').toLowerCase().includes(query);

                item.classList.toggle('hidden', !show);
                if (show) {
                    visible++;
                }
            });

            // Oculta la lista completa (tabla + tarjetas) si la búsqueda no arroja resultados.
            userList?.classList.toggle('hidden', visible === 0);
            userFilterEmpty?.classList.toggle('hidden', visible > 0);
        };

        userSearchInput?.addEventListener('input', applyUserFilter);

        userClearButton?.addEventListener('click', () => {
            if (userSearchInput) {
                userSearchInput.value = '';
            }
            applyUserFilter();
        });

        applyUserFilter();
    </script>
</x-layouts.sistema>
