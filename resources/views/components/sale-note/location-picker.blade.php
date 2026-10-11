@props([
    'latitude' => null,
    'longitude' => null,
])

@php
    $latValue = old('delivery_latitude', $latitude);
    $lngValue = old('delivery_longitude', $longitude);
    $modalName = 'sale-note-location-'.Illuminate\Support\Str::random(8);
@endphp

{{--
    Selector de ubicación de entrega.
    Los inputs llevan los nombres `delivery_latitude` / `delivery_longitude`, por
    lo que el formulario que lo contiene los envía igual que antes. El mapa se
    abre en un modal (`x-modal`) con el botón "Buscar en mapa"; también se pueden
    pegar coordenadas/enlaces de Google Maps o usar la ubicación del navegador.
--}}
<div class="mt-5 border-t border-gray-100 pt-5" data-location-picker data-location-modal="{{ $modalName }}">
    <div>
        <p class="mb-0 text-sm font-medium text-gray-700">Ubicación de entrega <span class="font-normal text-gray-400">(opcional)</span></p>
        <p class="mb-0 text-xs text-gray-500">Marca el punto en el mapa o pega un enlace de Google Maps.</p>
    </div>

    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <button type="button" data-location-open
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1 w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Buscar en mapa
        </button>

        <button type="button" data-location-gps
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60 w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            Usar mi ubicación
        </button>
    </div>

    {{-- Pegar enlace / coordenadas --}}
    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="delivery_location_link" class="mb-1 block text-sm font-medium text-gray-700">Enlace o coordenadas</label>
            <input type="text" id="delivery_location_link" data-location-link
                   autocomplete="off" spellcheck="false"
                   placeholder="Ej. https://maps.google.com/?q=-20.05,-63.52  o  -20.05, -63.52"
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
        </div>

        <button type="button" data-location-link-apply
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1 w-full sm:w-auto">
            Usar enlace
        </button>
    </div>

    {{-- Latitud / longitud (editables manualmente) --}}
    <div class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="delivery_latitude" class="mb-1 block text-sm font-medium text-gray-700">Latitud</label>
            <input type="number" id="delivery_latitude" name="delivery_latitude" data-location-latitude
                   value="{{ $latValue }}" step="any" inputmode="decimal"
                   placeholder="Ej. -20.039700" autocomplete="off"
                   @error('delivery_latitude') aria-invalid="true" @enderror
                   class="block w-full rounded-lg shadow-sm focus:ring-2 @error('delivery_latitude') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
            @error('delivery_latitude')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="delivery_longitude" class="mb-1 block text-sm font-medium text-gray-700">Longitud</label>
            <input type="number" id="delivery_longitude" name="delivery_longitude" data-location-longitude
                   value="{{ $lngValue }}" step="any" inputmode="decimal"
                   placeholder="Ej. -63.525300" autocomplete="off"
                   @error('delivery_longitude') aria-invalid="true" @enderror
                   class="block w-full rounded-lg shadow-sm focus:ring-2 @error('delivery_longitude') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
            @error('delivery_longitude')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p data-location-status class="mt-2 mb-0 hidden text-xs" role="status" aria-live="polite"></p>
    <p class="mt-2 mb-0 text-xs text-gray-400">La ubicación del navegador puede ser imprecisa; si no coincide, usa el mapa.</p>

    {{-- Modal: buscar en el mapa --}}
    <x-modal :name="$modalName" max-width="2xl" focusable>
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="mb-0 text-lg font-semibold text-gray-800">Buscar en el mapa</h2>
                    <p class="mb-0 mt-1 text-sm text-gray-500">Toca el mapa para marcar el punto de entrega o arrastra el pin.</p>
                </div>

                <button type="button" x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30"
                        aria-label="Cerrar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <div data-location-map
                     class="relative z-0 h-72 w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50 sm:h-96"
                     role="application" aria-label="Mapa para ubicar el punto de entrega"></div>

                <p data-location-fallback
                   class="mt-3 mb-0 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    No se pudo cargar el mapa. Ingresa las coordenadas manualmente o pega un enlace de Google Maps.
                </p>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1">
                    Cancelar
                </button>

                <button type="button" data-location-confirm
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2">
                    Usar esta ubicación
                </button>
            </div>
        </div>
    </x-modal>
</div>

@once
    @vite('resources/js/location-picker.js')
@endonce
