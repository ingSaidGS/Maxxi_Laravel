@props([
    'type' => 'submit',
    'pending' => 'Procesando…',
])

@php
    // Las dos capas ocupan la misma celda para que el botón conserve su ancho
    // cuando pasa al estado de carga (el contenido más ancho define el tamaño).
    $capaBase = 'col-start-1 row-start-1 inline-flex items-center justify-center gap-1.5';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'disabled:cursor-wait disabled:opacity-75']) }}>
    <span class="grid">
        <span data-submit-idle class="{{ $capaBase }}">
            {{ $slot }}
        </span>

        <span data-submit-loading class="{{ $capaBase }} invisible">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ $pending }}
        </span>
    </span>
</button>
