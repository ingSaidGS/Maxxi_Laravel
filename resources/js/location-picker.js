/**
 * Selector de ubicación de entrega (notas de venta).
 *
 * El mapa (Leaflet/OpenStreetMap) se muestra dentro del modal `x-modal`
 * "Buscar en mapa". El pin es temporal: los inputs `delivery_latitude` /
 * `delivery_longitude` solo se actualizan al confirmar ("Usar esta ubicación").
 * También sincroniza con el campo para pegar enlaces de Google Maps o
 * coordenadas "lat, lng" y con el botón de geolocalización del navegador.
 *
 * No depende del GPS del equipo (que en un escritorio puede resolver a la
 * ciudad del proveedor de internet): el pin se ubica a mano o pegando el
 * enlace/coordenadas que comparte el cliente.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Centro por defecto: Camiri (el local). El pin lo fija el usuario y puede
// arrastrar el mapa a cualquier punto. Cambia esta constante si el local se
// traslada.
const DEFAULT_CENTER = [-20.0397, -63.5253];
const DEFAULT_ZOOM = 13;
const PIN_ZOOM = 16;

// Pin propio (SVG en un divIcon) para no depender de las imágenes por defecto
// de Leaflet ni de rutas de assets al empaquetar.
const PIN_ICON = L.divIcon({
    className: 'maxxi-location-pin',
    html: '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="#F87613" stroke="#1A2E45" stroke-width="1.5" stroke-linejoin="round"><path d="M12 22s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10.5" r="2.6" fill="#FFFAE8" stroke="none"/></svg>',
    iconSize: [30, 30],
    iconAnchor: [15, 29],
    popupAnchor: [0, -28],
});

function isValid(lat, lng) {
    return Number.isFinite(lat) && Number.isFinite(lng)
        && lat >= -90 && lat <= 90
        && lng >= -180 && lng <= 180;
}

/**
 * Extrae lat/lng de un texto que puede ser:
 *  - coordenadas "lat, lng"
 *  - enlace de Google Maps (varias formas)
 *  - URI "geo:lat,lng"
 * Devuelve { lat, lng } o null.
 */
function parseCoordinates(raw) {
    const text = String(raw ?? '').trim();

    if (text === '') {
        return null;
    }

    const number = String.raw`(-?\d{1,3}(?:\.\d+)?)`;
    const patterns = [
        // data=...!3dLAT!4dLNG (enlaces de lugar)
        new RegExp(`!3d${number}!4d${number}`),
        // .../@LAT,LNG,zoom
        new RegExp(`@${number},${number}`),
        // ?q=LAT,LNG  |  &ll=LAT,LNG  |  center=LAT,LNG
        new RegExp(`[?&](?:q|query|ll|center)${number},${number}`, 'i'),
        // geo:LAT,LNG
        new RegExp(`geo:${number},${number}`, 'i'),
        // "LAT, LNG" a secas
        new RegExp(`${number}\\s*,\\s*${number}`),
    ];

    for (const pattern of patterns) {
        const match = text.match(pattern);

        if (match) {
            const lat = Number.parseFloat(match[1]);
            const lng = Number.parseFloat(match[2]);

            if (isValid(lat, lng)) {
                return { lat, lng };
            }
        }
    }

    return null;
}

function toFixed7(value) {
    return Number(value).toFixed(7);
}

function initPicker(root) {
    const mapEl = root.querySelector('[data-location-map]');
    const latInput = root.querySelector('[data-location-latitude]');
    const lngInput = root.querySelector('[data-location-longitude]');
    const linkInput = root.querySelector('[data-location-link]');
    const linkButton = root.querySelector('[data-location-link-apply]');
    const gpsButton = root.querySelector('[data-location-gps]');
    const openButton = root.querySelector('[data-location-open]');
    const confirmButton = root.querySelector('[data-location-confirm]');
    const statusEl = root.querySelector('[data-location-status]');
    const fallbackEl = root.querySelector('[data-location-fallback]');
    const modalName = root.getAttribute('data-location-modal');

    if (!mapEl || !latInput || !lngInput) {
        return;
    }

    let map = null;
    let marker = null;
    let committed = null;

    const setStatus = (message, type) => {
        if (!statusEl) {
            return;
        }

        const colors = {
            ok: 'text-green-700',
            error: 'text-red-600',
            info: 'text-gray-500',
        };

        statusEl.textContent = message ?? '';
        statusEl.className = 'mt-2 mb-0 text-xs ' + (colors[type] ?? colors.info);
        statusEl.classList.toggle('hidden', !message);
    };

    const readInputs = () => {
        const lat = Number.parseFloat(latInput.value);
        const lng = Number.parseFloat(lngInput.value);

        return isValid(lat, lng) ? { lat, lng } : null;
    };

    const writeInputs = (lat, lng) => {
        latInput.value = toFixed7(lat);
        lngInput.value = toFixed7(lng);
        committed = { lat, lng };
    };

    const syncMarker = (coords) => {
        if (!map || !coords) {
            return;
        }

        const latlng = [coords.lat, coords.lng];

        if (!marker) {
            marker = L.marker(latlng, { icon: PIN_ICON, draggable: true, autoPan: true }).addTo(map);
        } else {
            marker.setLatLng(latlng);
        }
    };

    const ensureMap = () => {
        if (map) {
            return map;
        }

        try {
            map = L.map(mapEl, { zoomControl: true, attributionControl: true });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            }).addTo(map);

            map.on('click', (event) => {
                syncMarker({ lat: event.latlng.lat, lng: event.latlng.lng });
            });
        } catch (error) {
            // Sin mapa disponible: se conservan los inputs manuales y el pegado.
            if (fallbackEl) {
                fallbackEl.classList.remove('hidden');
            }

            mapEl.classList.add('hidden');
            map = null;
        }

        return map;
    };

    // Al abrir el modal, el contenedor ya es visible: se inicializa (o se
    // recalcula el tamaño) y se centra en las coordenadas actuales.
    const prepareMap = () => {
        const current = committed ?? readInputs();
        const instance = ensureMap();

        if (!instance) {
            return;
        }

        instance.invalidateSize();

        if (current) {
            syncMarker(current);
            instance.setView([current.lat, current.lng], Math.max(instance.getZoom(), PIN_ZOOM));
        } else {
            if (marker) {
                instance.removeLayer(marker);
                marker = null;
            }

            instance.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }
    };

    if (modalName) {
        window.addEventListener('open-modal', (event) => {
            if (event.detail === modalName) {
                // Deja que Alpine muestre el modal antes de medir el mapa.
                window.setTimeout(prepareMap, 80);
            }
        });
    }

    // Abre el modal con el evento de la app (`x-modal`). No se usa `x-on:click`
    // en el botón porque vive fuera de un scope `x-data` y Alpine no engancha
    // ese listener en el render inicial.
    openButton?.addEventListener('click', () => {
        if (!modalName) {
            return;
        }

        window.dispatchEvent(new CustomEvent('open-modal', { detail: modalName }));
    });

    confirmButton?.addEventListener('click', () => {
        if (!marker) {
            setStatus('Toca el mapa para marcar el punto de entrega.', 'error');
            return;
        }

        const position = marker.getLatLng();
        writeInputs(position.lat, position.lng);
        setStatus('Ubicación aplicada desde el mapa.', 'ok');
        window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
    });

    const syncMarkerFromInputs = () => {
        const coords = readInputs();
        committed = coords;

        if (map && coords) {
            syncMarker(coords);
        }
    };

    latInput.addEventListener('change', syncMarkerFromInputs);
    lngInput.addEventListener('change', syncMarkerFromInputs);

    const applyLink = () => {
        const value = linkInput ? linkInput.value : '';
        const coords = parseCoordinates(value);

        if (coords) {
            writeInputs(coords.lat, coords.lng);
            syncMarker(coords);

            if (map) {
                map.setView([coords.lat, coords.lng], Math.max(map.getZoom(), PIN_ZOOM));
            }

            setStatus('Ubicación aplicada desde el enlace.', 'ok');
            return;
        }

        if (/(?:maps\.app\.goo\.gl|goo\.gl\/maps)/i.test(value)) {
            setStatus('Los enlaces cortos no se pueden leer. Ábrelo y copia las coordenadas o el enlace completo.', 'error');
            return;
        }

        setStatus(
            String(value).trim() === ''
                ? 'Pega un enlace de Google Maps o coordenadas "lat, lng".'
                : 'No se pudieron leer las coordenadas. Prueba con "lat, lng" o un enlace largo de Google Maps.',
            'error'
        );
    };

    linkButton?.addEventListener('click', applyLink);
    linkInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyLink();
        }
    });

    gpsButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Este navegador no soporta la geolocalización.', 'error');
            return;
        }

        gpsButton.disabled = true;
        setStatus('Obteniendo ubicación…', 'info');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                const precision = Math.round(accuracy || 0);

                writeInputs(latitude, longitude);
                syncMarker({ lat: latitude, lng: longitude });

                if (map) {
                    map.setView([latitude, longitude], Math.max(map.getZoom(), PIN_ZOOM));
                }

                setStatus(
                    'Ubicación capturada' + (precision > 0 ? ` (precisión ±${precision} m)` : '')
                        + '. Si no es exacta, usa el mapa o pega un enlace.',
                    precision > 500 ? 'error' : 'ok'
                );

                gpsButton.disabled = false;
            },
            () => {
                setStatus('No se pudo obtener la ubicación. Usa el mapa o pega un enlace.', 'error');
                gpsButton.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

function boot() {
    document.querySelectorAll('[data-location-picker]').forEach(initPicker);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
