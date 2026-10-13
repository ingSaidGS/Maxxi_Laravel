<x-guest-layout>
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="mb-1 text-2xl font-semibold text-gray-800">Iniciar sesión</h1>
        <p class="mb-0 text-sm text-gray-500">Ingresa con tu cuenta para continuar.</p>
    </div>

    {{-- Estado de sesión (p. ej. enlace de restablecimiento enviado) --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Correo electrónico --}}
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="correo@ejemplo.com"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Contraseña --}}
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Tu contraseña"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange"
            />
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Recordarme + olvido de contraseña --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    name="remember"
                    type="checkbox"
                    class="rounded border-gray-300 text-maxxi-orange shadow-sm focus:ring-maxxi-orange"
                />
                <span class="ms-2 text-sm text-gray-600">Recuérdame</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="rounded text-sm font-medium text-maxxi-orange transition hover:text-maxxi-orange-dark hover:underline focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2"
                >
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        {{-- Acción principal --}}
        <button
            type="submit"
            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2"
        >
            Iniciar sesión
        </button>
    </form>
</x-guest-layout>
