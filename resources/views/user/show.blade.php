@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Usuario: {{ $user->name }}">
    {{-- Encabezado de página + acciones --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mb-0 text-2xl font-semibold text-gray-800">{{ $user->name }}</h1>

                @if ($user->email_verified_at)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Verificado</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Sin verificar</span>
                @endif
            </div>
            <p class="mb-0 mt-1 text-sm text-gray-500">Usuario #{{ $user->id }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('users.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
            <a href="{{ route('users.edit', $user) }}" class="{{ $botones['marino'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                </svg>
                Editar
            </a>

            @unless ($user->is(auth()->user()))
                <form method="POST"
                      action="{{ route($user->active ? 'users.deactivate' : 'users.activate', $user) }}"
                      class="w-full sm:w-auto"
                      @if ($user->active) onsubmit="return confirm('¿Desactivar este usuario? Se cerrará su sesión de inmediato.');" @endif>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="{{ $user->active ? $botones['peligro'] : $botones['exito'] }} w-full sm:w-auto">
                        {{ $user->active ? 'Desactivar acceso' : 'Activar acceso' }}
                    </button>
                </form>
            @endunless
        </div>
    </div>

    {{-- Mensajes de error (p. ej. no dejar el sistema sin encargados) --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
            </svg>
            <div>
                <p class="mb-1 font-semibold">No se pudo completar la acción:</p>
                <ul class="mb-0 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Mensaje de éxito --}}
    @if (session('status'))
        <div role="status" class="mb-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Ficha de detalle --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <dl class="mb-0 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Nombre</dt>
                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $user->name }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Correo electrónico</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $user->email }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Rol</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($user->isEncargado())
                        <span class="inline-flex items-center rounded-full bg-maxxi-navy/10 px-2.5 py-0.5 text-xs font-medium text-maxxi-navy">Encargado</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Empleado</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Acceso</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($user->active)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activo</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Inactivo</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    @if ($user->email_verified_at)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Verificado</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Sin verificar</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Verificado el</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $user->email_verified_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creado</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $user->created_at?->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Actualizado</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $user->updated_at?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.sistema>
