@php
    // Variantes de botón reutilizables (ver skill maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Perfil">
    {{-- Encabezado de página --}}
    <div class="mb-6">
        <h1 class="mb-0 text-2xl font-semibold text-gray-800">Perfil</h1>
        <p class="mb-0 mt-1 text-sm text-gray-500">Administra tu información personal y tu contraseña.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Información del perfil --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Contraseña --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-layouts.sistema>
