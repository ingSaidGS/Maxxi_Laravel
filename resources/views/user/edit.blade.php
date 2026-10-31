@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Editar usuario">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Editar usuario</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Actualiza los datos del usuario #{{ $user->id }}.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('users.show', $user) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Ver detalle
            </a>
            <a href="{{ route('users.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
        </div>
    </div>

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

    {{-- Formulario --}}
    <form id="user-form" action="{{ route('users.update', $user) }}" method="POST"
          class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autofocus autocomplete="off" placeholder="Ej.: María López"
                       @error('name') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('name') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="off" placeholder="Ej.: maria@maxxi.com"
                       @error('email') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('email') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tipo" class="mb-1 block text-sm font-medium text-gray-700">Rol</label>
                <select id="tipo" name="tipo" required
                        @error('tipo') aria-invalid="true" @enderror
                        class="block w-full rounded-lg shadow-sm focus:ring-2 @error('tipo') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($userTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('tipo', $user->tipo->value) == $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('tipo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="Dejar en blanco para conservar la actual"
                       @error('password') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('password') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                <p class="mt-1 text-xs text-gray-500">Déjala en blanco si no quieres cambiarla. Si la cambias, mínimo 8 caracteres con letras y números.</p>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="Repite la nueva contraseña"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange/40" />
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('users.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Actualizar
            </button>
        </div>
    </form>

    {{-- Modal: confirmar la actualización del usuario --}}
    <x-modal name="confirm-user" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Actualizar este usuario?</h2>
                    <p id="confirm-user-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-user')">
                    Cancelar
                </button>
                <button type="button" id="confirm-user-button" class="{{ $botones['primario'] }}">
                    Sí, actualizar
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Confirmación antes de enviar el formulario --}}
    <script>
        (function () {
            var form = document.getElementById('user-form');
            var nameInput = document.getElementById('name');
            var emailInput = document.getElementById('email');
            var passwordInput = document.getElementById('password');
            var summary = document.getElementById('confirm-user-summary');
            var confirmButton = document.getElementById('confirm-user-button');
            var bypassConfirm = false;

            function openConfirmModal() {
                var name = (nameInput?.value || '').trim();
                var email = (emailInput?.value || '').trim();
                var changesPassword = (passwordInput?.value || '').length > 0;

                if (summary) {
                    summary.textContent = 'Se actualizarán los datos del usuario «' + name + '»'
                        + (email ? ' con el correo «' + email + '»' : '')
                        + (changesPassword ? ' y se cambiará su contraseña' : '') + '.';
                }

                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-user' }));
                    return;
                }

                // Respaldo por si el modal no está disponible.
                if (window.confirm('¿Actualizar el usuario «' + name + '»?')) {
                    bypassConfirm = true;
                    form.requestSubmit();
                    bypassConfirm = false;
                }
            }

            form.addEventListener('submit', function (event) {
                if (bypassConfirm) { return; }

                event.preventDefault();
                openConfirmModal();
            });

            confirmButton.addEventListener('click', function () {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-user' }));

                bypassConfirm = true;

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }

                bypassConfirm = false;
            });
        })();
    </script>
</x-layouts.sistema>
