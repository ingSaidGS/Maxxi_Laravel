@props([
    'id',
    'name' => null,
    // Clave de error que pinta el campo en rojo (por defecto, el propio "name").
    'errorKey' => null,
    // Bolsa de errores con nombre (p. ej. "updatePassword" en el perfil).
    'errorBag' => 'default',
])

@php
    $name ??= $id;
    $hasError = $errors->getBag($errorBag)->has($errorKey ?? $name);
    $stateClasses = $hasError
        ? 'border-red-300 focus:border-red-400 focus:ring-red-200'
        : 'border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40';
@endphp

{{--
    Campo de contraseña con botón "ojito" para mostrar u ocultar lo escrito.
    Acepta los atributos habituales del input (required, minlength,
    autocomplete, placeholder…), que se pasan tal cual.
--}}
<div class="relative">
    <input type="password" id="{{ $id }}" name="{{ $name }}"
           @if ($hasError) aria-invalid="true" @endif
           {{ $attributes->merge(['class' => 'block w-full rounded-lg pr-10 shadow-sm focus:ring-2 '.$stateClasses]) }} />

    <button type="button" data-password-toggle aria-controls="{{ $id }}" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña"
            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-gray-500 transition hover:text-maxxi-navy focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:text-maxxi-navy">
        {{-- Ojo abierto: trazo grueso y formas simples para que se lea bien. --}}
        <svg data-icon-show class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        {{-- Ojo tachado: contraseña visible. --}}
        <svg data-icon-hide class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.6 3.5" />
            <path d="M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a10.4 10.4 0 0 0 5.4-1.6" />
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
            <path d="M3 3l18 18" />
        </svg>
    </button>
</div>

@once
    <script>
        // Alterna la visibilidad del campo de contraseña asociado al botón.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-password-toggle]');
            if (!button) { return; }

            var input = document.getElementById(button.getAttribute('aria-controls'));
            if (!input) { return; }

            var show = input.type === 'password';
            var label = show ? 'Ocultar contraseña' : 'Mostrar contraseña';

            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.querySelector('[data-icon-show]')?.classList.toggle('hidden', show);
            button.querySelector('[data-icon-hide]')?.classList.toggle('hidden', !show);
        });
    </script>
@endonce
