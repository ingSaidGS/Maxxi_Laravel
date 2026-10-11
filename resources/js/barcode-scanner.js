/**
 * Escáner de códigos de barras con la cámara del dispositivo (ventas).
 *
 * Carga la librería html5-qrcode de forma diferida (solo la primera vez que se
 * abre el modal de escaneo) y expone un único punto de entrada en window:
 *
 *   window.MaxxiBarcodeScanner.start(containerId, onSuccess, onError)
 *   window.MaxxiBarcodeScanner.stop()
 *
 * Selección de cámara:
 *   1. Se enumeran las cámaras disponibles y se prefiere la "trasera" (rear),
 *      luego la "frontal" y, en último caso, la primera disponible. Así
 *      funciona en el teléfono (cámara trasera) y también en una laptop que
 *      solo tiene cámara frontal.
 *   2. Si la enumeración no devuelve nada, se reintenta con facingMode
 *      "environment" y luego "user" (comportamiento permisivo del navegador).
 *
 * La cámara siempre se libera al cerrar el modal; por eso `stop()` es
 * idempotente y `start()` detiene cualquier stream previo antes de abrir uno
 * nuevo.
 */

let scanner = null;
let libraryPromise = null;
let successPending = false;

function loadLibrary() {
    if (!libraryPromise) {
        libraryPromise = import('html5-qrcode').then((module) => module);
    }
    return libraryPromise;
}

/**
 * Elige la mejor cámara según su etiqueta. Devuelve una configuración de
 * `Html5Qrcode.start()` o `null` si no hay cámaras que enumerar.
 */
async function resolveCameraConfig(Html5Qrcode) {
    let cameras = [];
    try {
        cameras = await Html5Qrcode.getCameras();
    } catch (e) {
        cameras = [];
    }

    if (cameras.length === 0) { return null; }

    const like = (label, expression) => expression.test((label || '').toLowerCase());
    const rear = cameras.find((c) => like(c.label, /back|rear|environment|trasera|posterior/i));
    const front = cameras.find((c) => like(c.label, /front|user|frontal|selfie|delantera/i));
    const selected = rear || front || cameras[0];

    return { deviceId: { exact: selected.id } };
}

/**
 * Inicia la cámara y llama a `onSuccess(texto)` al decodificar un código.
 * Si todas las cámaras fallan, invoca `onError(error)`.
 */
async function start(containerId, onSuccess, onError) {
    await stop();

    successPending = false;

    try {
        const { Html5Qrcode, Html5QrcodeSupportedFormats } = await loadLibrary();

        const container = document.getElementById(containerId);
        if (!container) {
            throw new Error('No se encontró el contenedor de la cámara.');
        }

        const config = {
            fps: 10,
            // Área de enfoque adaptada al tamaño real del visor.
            qrbox: (viewfinderWidth, viewfinderHeight) => ({
                width: Math.min(280, Math.round(viewfinderWidth * 0.85)),
                height: Math.min(140, Math.round(viewfinderHeight * 0.45)),
            }),
            // Símbolos habituales en códigos de producto.
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.ITF,
                Html5QrcodeSupportedFormats.CODABAR,
            ],
        };

        const onDecoded = (decodedText) => {
            // Evita disparar la confirmación dos veces por el mismo código.
            if (successPending) { return; }
            successPending = true;
            onSuccess(decodedText);
        };

        // 1) Cámara concreta (trasera -> frontal -> primera).
        const preferred = await resolveCameraConfig(Html5Qrcode);

        // 2) Si no se pudo enumerar, se prueba facingMode en orden.
        const attempts = preferred
            ? [preferred]
            : [{ facingMode: 'environment' }, { facingMode: 'user' }];

        let lastError = null;

        for (const videoConfig of attempts) {
            try {
                scanner = new Html5Qrcode(containerId, false);
                await scanner.start(videoConfig, config, onDecoded, () => {
                    // Frames sin lectura: se ignoran.
                });
                return; // Cámara iniciada correctamente.
            } catch (error) {
                lastError = error;
                try { await scanner.stop(); } catch (e) { /* nunca arrancó */ }
                scanner = null;
            }
        }

        throw lastError || new Error('No se pudo iniciar la cámara.');
    } catch (error) {
        scanner = null;
        successPending = false;
        if (onError) { onError(error); }
    }
}

/**
 * Detiene la cámara y libera el stream. Seguro de llamar varias veces.
 */
async function stop() {
    if (!scanner) { return; }

    const current = scanner;
    scanner = null;
    successPending = false;

    try { await current.stop(); } catch (e) { /* ya estaba detenida */ }
    try { current.clear(); } catch (e) { /* sin stream que limpiar */ }
}

window.MaxxiBarcodeScanner = { start, stop };