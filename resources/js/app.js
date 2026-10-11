import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
 * Filas y tarjetas clicables.
 *
 * Cualquier elemento con [data-href] navega a esa URL al recibir un clic,
 * salvo que el clic haya sido sobre un control interactivo (enlaces, botones,
 * inputs, etc.) o sobre una zona marcada con [data-href-ignore] (por ejemplo,
 * la celda de acciones). También respeta Ctrl/Cmd/Shift/Alt y el clic central.
 */
document.addEventListener('click', (event) => {
    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return;
    }

    const interactive = event.target.closest(
        'a, button, input, select, textarea, label, [data-href-ignore]',
    );

    if (interactive) {
        return;
    }

    const target = event.target.closest('[data-href]');

    if (target && target.dataset.href) {
        window.location.href = target.dataset.href;
    }
});
