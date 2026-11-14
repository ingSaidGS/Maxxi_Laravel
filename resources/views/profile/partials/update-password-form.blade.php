<section>
    <header class="mb-4">
        <h2 class="mb-1 text-lg font-semibold text-gray-800">Actualizar contraseña</h2>
        <p class="mb-0 text-sm text-gray-500">Usa una contraseña larga y aleatoria para mantener tu cuenta segura.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        {{-- Contraseña actual --}}
        <div>
            <label for="update_password_current_password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña actual</label>
            <x-password-input id="update_password_current_password" name="current_password" error-bag="updatePassword" autocomplete="current-password" placeholder="Tu contraseña actual" />
            @error('current_password', 'updatePassword')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Nueva contraseña --}}
        <div>
            <label for="update_password_password" class="mb-1 block text-sm font-medium text-gray-700">Nueva contraseña</label>
            <x-password-input id="update_password_password" name="password" error-bag="updatePassword" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />
            @error('password', 'updatePassword')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirmar contraseña --}}
        <div>
            <label for="update_password_password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" error-bag="updatePassword" autocomplete="new-password" placeholder="Repite la nueva contraseña" />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-boton-envio pending="Actualizando contraseña…" class="{{ $botones['primario'] }}">Actualizar contraseña</x-boton-envio>

            @if (session('status') === 'password-updated')
                <span role="status" class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Contraseña actualizada.
                </span>
            @endif
        </div>
    </form>
</section>
