@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Nuevo usuario">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Nuevo usuario</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registra un usuario con acceso al sistema.</p>
        </div>

        <a href="{{ route('users.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al listado
        </a>
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
    <form id="user-form" action="{{ route('users.store') }}" method="POST"
          class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="255" required autofocus autocomplete="off" placeholder="Ej.: María López"
                       @error('name') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('name') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="off" placeholder="Ej.: maria@maxxi.com"
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
                        <option value="{{ $type->value }}" @selected(old('tipo') == $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('tipo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                       @error('password') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('password') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password" placeholder="Repite la contraseña"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange/40" />
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('users.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Guardar
            </button>
        </div>
    </form>

    {{-- Modal: confirmar la creación del usuario --}}
    <x-modal name="confirm-user" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Registrar este usuario?</h2>
                    <p id="confirm-user-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-user')">
                    Cancelar
                </button>
                <button type="button" id="confirm-user-button" class="{{ $botones['primario'] }}">
                    Sí, registrar
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
            var summary = document.getElementById('confirm-user-summary');
            var confirmButton = document.getElementById('confirm-user-button');
            var bypassConfirm = false;

            function openConfirmModal() {
                var name = (nameInput?.value || '').trim();
                var email = (emailInput?.value || '').trim();

                if (summary) {
                    summary.textContent = 'Se creará el usuario «' + name + '» con el correo «' + email + '».';
                }

                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-user' }));
                    return;
                }

                // Respaldo por si el modal no está disponible.
                if (window.confirm('¿Registrar el usuario «' + name + '»?')) {
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
