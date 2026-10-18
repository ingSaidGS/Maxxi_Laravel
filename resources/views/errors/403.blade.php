{{--
    Página 403 — acceso denegado.
    Laravel la muestra automáticamente cuando abort(403) o el middleware
    `can:` rechazan la petición. Solo indica el rol del usuario, sin
    exponer detalles sensibles de la política de permisos.
--}}
@php
    $user = auth()->user();
    $rol = $user?->isEncargado()
        ? 'Encargado'
        : ($user?->isEmpleado() ? 'Empleado' : null);
@endphp

<x-layouts.sistema title="403 · Sin permiso">
    <div class="mx-auto max-w-lg">
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-black/5 sm:p-10">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-maxxi-navy/10 text-maxxi-navy">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </span>

            <p class="mt-6 text-5xl font-semibold text-maxxi-navy">403</p>

            <h1 class="mt-2 text-2xl font-semibold text-gray-800">
                No tienes permiso para acceder a esta sección
            </h1>

            <p class="mt-3 text-sm text-gray-500">
                @if ($rol)
                    Tu rol actual es <span class="font-semibold text-gray-700">{{ $rol }}</span>.
                    Esta sección está reservada a usuarios con otros permisos.
                    Si necesitas acceso, solicítalo a un encargado.
                @else
                    Inicia sesión con una cuenta que tenga los permisos necesarios.
                @endif
            </p>

            <div class="mt-8 flex justify-center">
                <a
                    href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ auth()->check() ? 'Volver al Dashboard' : 'Iniciar sesión' }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.sistema>
