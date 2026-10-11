@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
    ];

    // Si la venta ya está pagada, los montos de pago quedan bloqueados
    // (solo se permite actualizar el cliente y la fecha).
    $isPaid = $sale->status === 'pagada';
@endphp

<x-layouts.sistema title="Editar venta #{{ $sale->id }}">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Editar venta</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Venta #{{ $sale->id }} · actualiza el cliente, la fecha y el pago.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('sales.show', $sale) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Ver detalle
            </a>
            <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
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

    <form id="sale-form" action="{{ route('sales.update', $sale) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Aviso: campos derivados --}}
        <div class="flex items-center gap-3 rounded-lg border border-maxxi-navy/15 bg-maxxi-navy/5 px-4 py-3">
            <svg class="h-5 w-5 shrink-0 text-maxxi-navy" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="mb-0 text-sm text-gray-600">El estado (pagada/fiada), el fio y el cajero se determinan automáticamente al guardar.</p>
        </div>

        {{-- Sección: Datos de la venta --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Datos de la venta</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="customer_name" class="mb-1 block text-sm font-medium text-gray-700">
                        Cliente <span class="font-normal text-gray-400">(obligatorio si la venta queda fiada)</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}" maxlength="20"
                           placeholder="Ej. Juan Pérez" autocomplete="off"
                           @error('customer_name') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('customer_name') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('customer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_phone" class="mb-1 block text-sm font-medium text-gray-700">
                        Teléfono <span class="font-normal text-gray-400">(obligatorio si la venta queda fiada)</span>
                    </label>
                    <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $sale->customer_phone) }}" maxlength="10"
                           placeholder="Ej. 77712345" inputmode="tel" autocomplete="off"
                           @error('customer_phone') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('customer_phone') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('customer_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="sold_at" class="mb-1 block text-sm font-medium text-gray-700">Fecha de venta</label>
                    <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at', $sale->sold_at?->format('Y-m-d\TH:i')) }}" required
                           @error('sold_at') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 sm:max-w-xs @error('sold_at') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('sold_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sección: Pago --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Pago</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label for="sale_discount" class="mb-1 block text-sm font-medium text-gray-700">Descuento (Bs.)</label>
                    <input type="number" id="sale_discount" name="sale_discount" value="{{ old('sale_discount', $sale->sale_discount) }}" step="0.1" min="0" inputmode="decimal" required
                           placeholder="Bs. 0.0" @readonly($isPaid)
                           @error('sale_discount') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm @if ($isPaid) cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 @else focus:ring-2 @error('sale_discount') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror @endif" />
                    @error('sale_discount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cash" class="mb-1 block text-sm font-medium text-gray-700">Efectivo (Bs.)</label>
                    <input type="number" id="cash" name="cash" value="{{ old('cash', $sale->cash) }}" step="0.1" min="0" inputmode="decimal" required
                           placeholder="Bs. 0.0" @readonly($isPaid)
                           @error('cash') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm @if ($isPaid) cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 @else focus:ring-2 @error('cash') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror @endif" />
                    @error('cash')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="qr" class="mb-1 block text-sm font-medium text-gray-700">QR (Bs.)</label>
                    <input type="number" id="qr" name="qr" value="{{ old('qr', $sale->qr) }}" step="0.1" min="0" inputmode="decimal" required
                           placeholder="Bs. 0.0" @readonly($isPaid)
                           @error('qr') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm @if ($isPaid) cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 @else focus:ring-2 @error('qr') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror @endif" />
                    @error('qr')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Resumen calculado --}}
            <div class="mt-5 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 bg-gray-50/60 p-4 sm:grid-cols-4">
                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total (calculado)</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-gray-800"><span id="total-display">Bs. {{ number_format((float) $sale->total, 1) }}</span></p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</p>
                    <p class="mb-0 mt-1">
                        <span id="status-display" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sale->status === 'pagada' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $sale->status }}</span>
                    </p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fio (deuda)</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-gray-800"><span id="debt-display">Bs. {{ number_format((float) $sale->debt, 1) }}</span></p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Vuelto</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-maxxi-navy"><span id="change-display">Bs. 0.0</span></p>
                </div>
            </div>

            <p id="applied-cash-note" class="mt-2 mb-0 hidden text-xs font-medium text-maxxi-navy"></p>

            @if ($isPaid)
                <p class="mt-2 mb-0 text-xs text-amber-600">Venta pagada: el descuento, el efectivo y el QR quedan bloqueados. Solo puedes actualizar el cliente y la fecha.</p>
            @else
                <p class="mt-2 mb-0 text-xs text-gray-500">El total se recalcula a partir de los detalles y el descuento. Si efectivo + QR es menor que el total, se marca como FIADA; si es mayor, el vuelto se devuelve del efectivo y solo se registra lo justo.</p>
            @endif
        </div>

        {{-- Acciones --}}
        <div class="flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Actualizar
            </button>
        </div>
    </form>

    {{-- Modal: confirmar actualización de la venta --}}
    <x-modal name="confirm-sale" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Actualizar la venta?</h2>
                    <p id="confirm-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-sale')">
                    Cancelar
                </button>
                <button type="button" id="confirm-sale-button" class="{{ $botones['primario'] }}">
                    Sí, actualizar
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Modal: confirmar venta fiada --}}
    <x-modal name="confirm-fiada" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Confirmar venta fiada?</h2>
                    <p class="mb-1 text-sm text-gray-600">
                        Esta venta se guardará como <span class="font-semibold text-amber-700">FIADA</span>.
                    </p>
                    <p id="fiada-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-fiada')">
                    Cancelar
                </button>
                <button type="button" id="confirm-fiada-button" class="{{ $botones['primario'] }}">
                    Sí, guardar fiada
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        (function () {
            var subtotals = {{ (float) $subtotals }};

            var form = document.getElementById('sale-form');
            var discountInput = document.getElementById('sale_discount');
            var cashInput = document.getElementById('cash');
            var qrInput = document.getElementById('qr');
            var nameInput = document.getElementById('customer_name');
            var phoneInput = document.getElementById('customer_phone');
            var totalDisplay = document.getElementById('total-display');
            var debtDisplay = document.getElementById('debt-display');
            var changeDisplay = document.getElementById('change-display');
            var appliedCashNote = document.getElementById('applied-cash-note');
            var statusDisplay = document.getElementById('status-display');

            var statusPaidClass = 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700';
            var statusDebtClass = 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700';

            function money(n) {
                return 'Bs. ' + (Math.round(n * 10) / 10).toFixed(1);
            }

            function parseMoney(v) {
                var s = (v === undefined || v === null) ? '' : String(v).trim();
                if (s === '') return 0;
                s = s.replace(',', '.');
                var num = parseFloat(s);
                return isNaN(num) ? 0 : num;
            }

            function computeAmounts() {
                var discount = Math.max(0, parseMoney(discountInput.value));
                var total = Math.round(Math.max(0, subtotals - discount) * 10) / 10;
                var cash = parseMoney(cashInput.value);
                var qr = parseMoney(qrInput.value);
                var paid = cash + qr;
                var fiada = paid < total - 0.05;

                // El vuelto se devuelve del efectivo: nunca se registra un pago mayor
                // al total. "cashApplied" es el efectivo que realmente se guardará.
                var change = fiada ? 0 : Math.round(Math.max(0, paid - total) * 10) / 10;
                var cashApplied = change > 0
                    ? Math.max(0, Math.round((total - qr) * 10) / 10)
                    : cash;

                return {
                    total: total,
                    paid: paid,
                    cash: cash,
                    qr: qr,
                    cashApplied: cashApplied,
                    change: change,
                    fiada: fiada,
                    debt: fiada ? Math.max(0, total - paid) : 0,
                };
            }

            function render() {
                var amounts = computeAmounts();

                totalDisplay.textContent = money(amounts.total);
                debtDisplay.textContent = money(amounts.debt);
                changeDisplay.textContent = money(amounts.change);
                statusDisplay.textContent = amounts.fiada ? 'fiada' : 'pagada';
                statusDisplay.className = amounts.fiada ? statusDebtClass : statusPaidClass;

                if (appliedCashNote) {
                    if (amounts.change > 0) {
                        appliedCashNote.textContent = 'Se registrarán ' + money(amounts.cashApplied) + ' en efectivo; el vuelto de ' + money(amounts.change) + ' se devuelve al cliente.';
                        appliedCashNote.classList.remove('hidden');
                    } else {
                        appliedCashNote.classList.add('hidden');
                    }
                }

                nameInput.required = amounts.fiada;
                phoneInput.required = amounts.fiada;
            }

            discountInput.addEventListener('input', render);
            cashInput.addEventListener('input', render);
            qrInput.addEventListener('input', render);

            var bypassConfirm = false;
            var confirmSummary = document.getElementById('confirm-summary');
            var fiadaSummary = document.getElementById('fiada-summary');
            var confirmButton = document.getElementById('confirm-sale-button');
            var fiadaButton = document.getElementById('confirm-fiada-button');

            // Abre el modal de confirmación correspondiente: uno específico cuando la
            // venta queda "fiada" y el genérico cuando queda "pagada".
            function openConfirmModal() {
                var amounts = computeAmounts();

                if (!window.Alpine) {
                    // Respaldo por si el modal no está disponible.
                    var fallback = amounts.fiada
                        ? 'La venta quedará marcada como FIADA por un fio de ' + money(amounts.debt) + '.'
                        : 'Vas a actualizar la venta por ' + money(amounts.total) + '.';

                    if (window.confirm(fallback + ' ¿Deseas continuar?')) {
                        bypassConfirm = true;
                        form.submit();
                        bypassConfirm = false;
                    }
                    return;
                }

                if (amounts.fiada) {
                    if (fiadaSummary) {
                        fiadaSummary.textContent = 'El cliente quedará con un fio pendiente de ' + money(amounts.debt) + '.';
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-fiada' }));
                    return;
                }

                if (confirmSummary) {
                    var message = 'Vas a actualizar la venta por ' + money(amounts.total) + '.';

                    if (amounts.change > 0) {
                        message += ' Se devolverá un vuelto de ' + money(amounts.change) + ' y se registrarán ' + money(amounts.cashApplied) + ' en efectivo.';
                    }

                    confirmSummary.textContent = message;
                }
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-sale' }));
            }

            form.addEventListener('submit', function (event) {
                if (bypassConfirm) { return; }

                event.preventDefault();
                openConfirmModal();
            });

            // Ambos modales (pagada y fiada) confirman enviando el formulario.
            function submitConfirmedSale() {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-sale' }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-fiada' }));

                bypassConfirm = true;

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }

                bypassConfirm = false;
            }

            if (confirmButton) {
                confirmButton.addEventListener('click', submitConfirmedSale);
            }

            if (fiadaButton) {
                fiadaButton.addEventListener('click', submitConfirmedSale);
            }

            render();
        })();
    </script>
</x-layouts.sistema>
