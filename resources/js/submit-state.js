/*
 * Estado de carga de formularios.
 *
 * Al enviar un formulario, deshabilita sus botones de envío y muestra un
 * spinner con un texto en gerundio («Guardando…», «Actualizando…»). También
 * evita envíos duplicados por doble clic o Enter repetido.
 *
 * El texto se puede personalizar con data-pending en el botón o en el form.
 * Los formularios que interceptan el submit (AJAX o confirmación propia)
 * quedan intactos: el evento llega con defaultPrevented = true.
 * Para excluir un formulario de forma explícita: data-no-loading.
 */

const PENDING_BY_METHOD = {
    get: 'Buscando…',
    post: 'Guardando…',
    put: 'Actualizando…',
    patch: 'Actualizando…',
    delete: 'Procesando…',
};

let statusRegion = null;

function announce(message) {
    if (!statusRegion) {
        statusRegion = document.createElement('p');
        statusRegion.id = 'submit-status';
        statusRegion.setAttribute('role', 'status');
        statusRegion.setAttribute('aria-live', 'polite');
        statusRegion.className = 'sr-only';
        document.body.appendChild(statusRegion);
    }

    statusRegion.textContent = message || '';
}

function createSpinner() {
    const namespace = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(namespace, 'svg');

    svg.setAttribute('class', 'h-4 w-4 animate-spin');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');

    const circle = document.createElementNS(namespace, 'circle');
    circle.setAttribute('class', 'opacity-25');
    circle.setAttribute('cx', '12');
    circle.setAttribute('cy', '12');
    circle.setAttribute('r', '10');
    circle.setAttribute('stroke', 'currentColor');
    circle.setAttribute('stroke-width', '4');

    const path = document.createElementNS(namespace, 'path');
    path.setAttribute('class', 'opacity-75');
    path.setAttribute('fill', 'currentColor');
    path.setAttribute('d', 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z');

    svg.append(circle, path);

    return svg;
}

function createPendingContent(text) {
    const wrapper = document.createElement('span');
    wrapper.className = 'inline-flex items-center justify-center gap-1.5';
    wrapper.append(createSpinner(), document.createTextNode(text));

    return wrapper;
}

/*
 * Alterna las capas idle/carga de un botón creado con <x-boton-envio> sin
 * reemplazar el DOM. Devuelve false si el botón no usa el componente (para
 * aplicar el respaldo genérico).
 */
function toggleComponent(button, loading) {
    const idle = button.querySelector('[data-submit-idle]');
    const pending = button.querySelector('[data-submit-loading]');

    if (!idle || !pending) {
        return false;
    }

    idle.classList.toggle('invisible', loading);
    pending.classList.toggle('invisible', !loading);

    return true;
}

function activateButton(button, text) {
    if (!button || button.disabled || button.dataset.loading === '1') {
        return;
    }

    button.dataset.loading = '1';
    button.style.minWidth = button.offsetWidth + 'px';
    button.classList.add('whitespace-nowrap');
    button.setAttribute('aria-busy', 'true');

    if (!toggleComponent(button, true)) {
        button.dataset.originalContent = button.innerHTML;
        button.replaceChildren(createPendingContent(text));
    }

    button.disabled = true;
}

function resetButton(button) {
    if (button.dataset.loading !== '1') {
        return;
    }

    delete button.dataset.loading;
    button.disabled = false;
    button.removeAttribute('aria-busy');

    if (!toggleComponent(button, false) && button.dataset.originalContent !== undefined) {
        button.innerHTML = button.dataset.originalContent;
        delete button.dataset.originalContent;
    }

    button.style.minWidth = '';
    button.classList.remove('whitespace-nowrap');
}

function resetForm(form) {
    delete form.dataset.submitting;
    form.removeAttribute('aria-busy');

    form.querySelectorAll('button[data-loading="1"]').forEach(resetButton);
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (event.defaultPrevented || form.hasAttribute('data-no-loading')) {
        return;
    }

    // Segundo intento mientras el primero sigue en curso.
    if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = '1';
    form.setAttribute('aria-busy', 'true');

    const method = (form.getAttribute('method') || 'get').toLowerCase();
    const defaultText = form.dataset.pending || PENDING_BY_METHOD[method] || 'Procesando…';
    const buttons = Array.from(form.querySelectorAll('button[type="submit"]')).filter(
        (button) => !button.disabled,
    );

    const submitter =
        event.submitter instanceof HTMLButtonElement && !event.submitter.disabled
            ? event.submitter
            : buttons[0];

    const text = submitter?.dataset.pending || defaultText;

    buttons.forEach((button) => activateButton(button, button.dataset.pending || text));

    announce(text);
});

// Restaura el estado al volver con el botón «atrás» (bfcache) o al reentrar.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting="1"]').forEach(resetForm);
    document.querySelectorAll('button[data-loading="1"]').forEach(resetButton);
    announce('');
});
