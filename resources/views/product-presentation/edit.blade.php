@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Editar presentación">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Editar presentación</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Actualiza los datos de la presentación #{{ $presentation->id }}.</p>
        </div>

        <a href="{{ route('products.show', $presentation->product_id) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al producto
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
    <form id="presentation-form" action="{{ route('product-presentations.update', $presentation) }}" method="POST"
          class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')

        <div class="mb-5 flex items-center gap-3 rounded-lg border border-maxxi-navy/15 bg-maxxi-navy/5 px-4 py-3">
            <svg class="h-5 w-5 text-maxxi-navy" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>
            <div>
                <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Producto</p>
                <p class="mb-0 text-sm font-semibold text-gray-800">{{ $presentation->product->name }}</p>
            </div>
            <input type="hidden" name="product_id" value="{{ $presentation->product_id }}">
        </div>

        {{-- Leyenda de campos obligatorios --}}
        <p class="mb-4 mt-0 text-xs text-gray-500">
            Los campos marcados con <span class="font-semibold text-red-600" aria-hidden="true">*</span> son obligatorios.
        </p>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="unit_id" class="mb-1 block text-sm font-medium text-gray-700">
                    Unidad <span class="font-semibold text-red-600" aria-hidden="true">*</span>
                </label>
                <select id="unit_id" name="unit_id" required
                        @error('unit_id') aria-invalid="true" @enderror
                        class="block w-full rounded-lg shadow-sm focus:ring-2 @error('unit_id') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id', $presentation->unit_id) == $unit->id)>
                            {{ $unit->name }} ({{ $unit->symbol }})
                        </option>
                    @endforeach
                </select>
                @error('unit_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="conversion_factor" class="mb-1 block text-sm font-medium text-gray-700">
                    Factor de conversión <span class="font-semibold text-red-600" aria-hidden="true">*</span>
                </label>
                <input type="number" id="conversion_factor" name="conversion_factor"
                       value="{{ old('conversion_factor', $presentation->conversion_factor) }}" step="1" min="0" required placeholder="Ej.: 24"
                       @error('conversion_factor') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('conversion_factor') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('conversion_factor')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <span class="mb-2 block text-sm font-medium text-gray-700">
                    Tamaño <span class="font-semibold text-red-600" aria-hidden="true">*</span>
                </span>
                <div class="flex flex-wrap gap-x-8 gap-y-3 rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                    <label for="presentation_size_pequeno" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="radio" id="presentation_size_pequeno" name="presentation_size" value="pequeño" required
                               @checked(old('presentation_size', $presentation->presentation_size) === 'pequeño')
                               class="h-4 w-4 border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Pequeño
                    </label>

                    <label for="presentation_size_grande" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="radio" id="presentation_size_grande" name="presentation_size" value="grande"
                               @checked(old('presentation_size', $presentation->presentation_size) === 'grande')
                               class="h-4 w-4 border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Grande
                    </label>
                </div>
                @error('presentation_size')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <span class="mb-2 block text-sm font-medium text-gray-700">Disponibilidad</span>
                <div class="flex flex-wrap gap-x-8 gap-y-3 rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                    <label for="purchase_enable" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="hidden" name="purchase_enable" value="0">
                        <input type="checkbox" id="purchase_enable" name="purchase_enable" value="1" @checked(old('purchase_enable', $presentation->purchase_enable))
                               class="h-4 w-4 rounded border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Compra habilitada
                    </label>

                    <label for="sale_enable" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="hidden" name="sale_enable" value="0">
                        <input type="checkbox" id="sale_enable" name="sale_enable" value="1" @checked(old('sale_enable', $presentation->sale_enable))
                               class="h-4 w-4 rounded border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Venta habilitada
                    </label>
                </div>
            </div>

            <div id="reference-purchase-cost-wrapper" class="sm:col-span-2" hidden>
                <label for="reference_purchase_cost" class="mb-1 block text-sm font-medium text-gray-700">
                    Costo de compra de referencia <span class="font-semibold text-red-600" aria-hidden="true">*</span>
                </label>
                <input type="number" id="reference_purchase_cost" name="reference_purchase_cost"
                       value="{{ old('reference_purchase_cost', $presentation->reference_purchase_cost) }}" step="0.01" min="0" @required(old('purchase_enable', $presentation->purchase_enable)) placeholder="Ej.: 120.00"
                       @error('reference_purchase_cost') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('reference_purchase_cost') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('reference_purchase_cost')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="sale-price-wrapper" class="sm:col-span-2" hidden>
                <label for="sale_price" class="mb-1 block text-sm font-medium text-gray-700">Precio de venta</label>
                <input type="number" id="sale_price" name="sale_price"
                       value="{{ old('sale_price', $presentation->sale_price) }}" step="0.1" min="0" placeholder="Ej.: 25.50"
                       @error('sale_price') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('sale_price') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('sale_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Costo de la presentación: costo por unidad mínima × factor de conversión --}}
            <div id="presentation-cost-wrapper" class="sm:col-span-2" hidden>
                <div class="flex items-center justify-between gap-3 rounded-lg border border-maxxi-navy/15 bg-maxxi-navy/5 px-4 py-3">
                    <div>
                        <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Costo de esta presentación</p>
                        <p id="presentation-cost-detail" class="mb-0 mt-0.5 text-xs text-gray-500"></p>
                    </div>
                    <p id="presentation-cost" class="mb-0 whitespace-nowrap text-lg font-semibold text-gray-800"></p>
                </div>
            </div>

            <div id="desired-profit-wrapper">
                <label for="desired_profit" class="mb-1 block text-sm font-medium text-gray-700">
                    Ganancia deseada <span class="font-semibold text-red-600" aria-hidden="true">*</span>
                </label>
                <input type="number" id="desired_profit" name="desired_profit"
                       value="{{ old('desired_profit', $presentation->desired_profit) }}" step="0.1" min="0" @required(old('sale_enable', $presentation->sale_enable)) placeholder="Ej.: 5.50"
                       @error('desired_profit') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('desired_profit') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('desired_profit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="barcode" class="mb-1 block text-sm font-medium text-gray-700">Código de barras <span class="font-normal text-gray-400">(opcional)</span></label>
                <div class="flex items-stretch gap-2">
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $presentation->barcode) }}" maxlength="20" autocomplete="off" placeholder="Ej.: 7501234567890"
                           @error('barcode') aria-invalid="true" @enderror
                           class="block w-full min-w-0 flex-1 rounded-lg shadow-sm focus:ring-2 @error('barcode') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    <button type="button" id="barcode-scan-open" aria-label="Escanear código de barras" title="Escanear código de barras"
                            class="inline-flex w-10 shrink-0 items-center justify-center rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5v9M6.75 7.5v9M9.75 7.5v2.25M9.75 14.25v2.25M12.75 7.5v2.25M12.75 14.25v2.25M15.75 7.5v9M18.75 7.5v2.25M18.75 14.25v2.25M21 9v6" />
                        </svg>
                    </button>
                </div>
                @error('barcode')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('products.show', $presentation->product_id) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <x-boton-envio pending="Actualizando…" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Actualizar
            </x-boton-envio>
        </div>
    </form>

    <script>
        const purchaseToggle = document.querySelector('input[name="purchase_enable"][value="1"]');
        const saleToggle = document.querySelector('input[name="sale_enable"][value="1"]');
        const costWrapper = document.getElementById('reference-purchase-cost-wrapper');
        const costInput = document.getElementById('reference_purchase_cost');
        const saleWrapper = document.getElementById('sale-price-wrapper');
        const saleInput = document.getElementById('sale_price');
        const conversionInput = document.getElementById('conversion_factor');
        const profitInput = document.getElementById('desired_profit');
        const profitWrapper = document.getElementById('desired-profit-wrapper');

        // Costo del producto por unidad mínima, tomado de la presentación de compra
        // que ya exista para este producto (null si no hay ninguna).
        const storedCostPerBaseUnit = {{ $purchaseCostPerBaseUnit !== null ? json_encode($purchaseCostPerBaseUnit) : 'null' }};

        // Si esta presentación es la de compra, el costo por unidad mínima se toma en
        // vivo del costo de referencia que se está escribiendo (null si aún no es válido).
        const currentCostPerBaseUnit = () => {
            if (!purchaseToggle.checked) {
                return storedCostPerBaseUnit;
            }

            const referenceCost = parseFloat(costInput.value);
            const conversion = parseInt(conversionInput.value, 10);

            if (!Number.isFinite(referenceCost) || !(conversion > 0)) {
                return null;
            }

            return referenceCost / conversion;
        };

        // Mismo redondeo que ProductPresentationService::roundSalePrice() al guardar
        // la presentación de compra: decimal < 0.3 -> N; 0.3–0.6 -> N.5; > 0.6 -> N+1.
        const roundSalePrice = (value) => {
            const integer = Math.floor(value);
            const hundredths = Math.round((value - integer) * 100);

            if (hundredths < 30) {
                return integer;
            }

            return hundredths <= 60 ? integer + 0.5 : integer + 1;
        };

        const calculateSalePrice = () => {
            if (!saleToggle.checked) {
                return;
            }

            const costPerBaseUnit = currentCostPerBaseUnit();

            if (costPerBaseUnit === null) {
                // Costo de compra vacío o inválido mientras se edita: sin vista previa.
                if (purchaseToggle.checked) {
                    saleInput.value = '';
                }

                return;
            }

            const conversion = parseFloat(conversionInput.value) || 0;
            const profit = parseFloat(profitInput.value) || 0;
            const salePrice = costPerBaseUnit * conversion + profit;

            // Con compra habilitada el servidor recalcula con el redondeo especial;
            // sin ella se guarda el valor enviado, redondeado a 1 decimal.
            const rounded = purchaseToggle.checked
                ? roundSalePrice(salePrice)
                : Math.round(salePrice * 10) / 10;

            saleInput.value = rounded.toFixed(1);
        };

        const syncFields = () => {
            const purchaseEnabled = purchaseToggle.checked;
            const saleEnabled = saleToggle.checked;

            costWrapper.hidden = !purchaseEnabled;
            costInput.disabled = !purchaseEnabled;
            costInput.required = purchaseEnabled;
            if (!purchaseEnabled) {
                costInput.value = '';
            }

            // Sin presentación de compra activa no se puede fijar el precio de venta.
            // Si esta misma es la de compra, el costo sale del campo de referencia.
            const salePriceLocked = !purchaseEnabled && storedCostPerBaseUnit === null;

            // La ganancia deseada solo aplica a presentaciones de venta; sin venta
            // no se envía y el servidor guarda 0.
            profitWrapper.hidden = !saleEnabled;
            profitInput.disabled = !saleEnabled;
            profitInput.required = saleEnabled;

            saleWrapper.hidden = !saleEnabled;
            saleInput.disabled = !saleEnabled || salePriceLocked;
            saleInput.placeholder = salePriceLocked ? 'Requiere una presentación de compra activa' : 'Ej.: 25.50';
            if (!saleEnabled || salePriceLocked) {
                saleInput.value = '';
            }
        };

        purchaseToggle.addEventListener('change', () => {
            syncFields();
            calculateSalePrice();
        });
        saleToggle.addEventListener('change', () => {
            syncFields();
            calculateSalePrice();
        });
        costInput.addEventListener('input', calculateSalePrice);
        conversionInput.addEventListener('input', calculateSalePrice);
        profitInput.addEventListener('input', calculateSalePrice);

        syncFields();

        // Solo se precarga si no hay un valor previo (p. ej. tras un error de validación).
        if (saleInput.value.trim() === '') {
            calculateSalePrice();
        }

        // Muestra cuánto cuesta esta presentación (costo por unidad mínima × factor).
        const presentationCostWrapper = document.getElementById('presentation-cost-wrapper');
        const presentationCost = document.getElementById('presentation-cost');
        const presentationCostDetail = document.getElementById('presentation-cost-detail');

        const updatePresentationCost = () => {
            const unitCost = currentCostPerBaseUnit();
            const conversion = parseInt(conversionInput.value, 10);

            if (unitCost === null || !(conversion > 0)) {
                presentationCostWrapper.hidden = true;
                return;
            }

            presentationCost.textContent = 'Bs. ' + (unitCost * conversion).toFixed(2);
            presentationCostDetail.textContent = 'Bs. ' + unitCost.toFixed(2) + ' por unidad mínima × ' + conversion;
            presentationCostWrapper.hidden = false;
        };

        // Se escucha en el formulario para que corra después de los demás listeners.
        document.getElementById('presentation-form').addEventListener('input', updatePresentationCost);
        document.getElementById('presentation-form').addEventListener('change', updatePresentationCost);
        updatePresentationCost();
    </script>

    {{-- Modal: confirmar la actualización de la presentación --}}
    <x-modal name="confirm-presentation" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Actualizar esta presentación?</h2>
                    <p id="confirm-presentation-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-presentation')">
                    Cancelar
                </button>
                <button type="button" id="confirm-presentation-button" class="{{ $botones['primario'] }}">
                    Sí, actualizar
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Confirmación antes de enviar el formulario --}}
    <script>
        (function () {
            var form = document.getElementById('presentation-form');
            var unitSelect = document.getElementById('unit_id');
            var factorInput = document.getElementById('conversion_factor');
            var summary = document.getElementById('confirm-presentation-summary');
            var confirmButton = document.getElementById('confirm-presentation-button');
            var productName = @json($presentation->product->name);
            var bypassConfirm = false;

            function selectedUnitLabel() {
                if (!unitSelect || unitSelect.selectedIndex < 0) {
                    return '';
                }

                return (unitSelect.options[unitSelect.selectedIndex]?.text || '')
                    .trim()
                    .replace(/\s+/g, ' ');
            }

            function openConfirmModal() {
                var unit = selectedUnitLabel();
                var factor = (factorInput?.value || '').trim();
                var label = unit && factor ? unit + ' (x' + factor + ')' : unit;

                if (summary) {
                    summary.textContent = 'Se actualizará la presentación'
                        + (label ? ' «' + label + '»' : '')
                        + ' del producto «' + productName + '».';
                }

                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-presentation' }));
                    return;
                }

                // Respaldo por si el modal no está disponible.
                if (window.confirm('¿Actualizar la presentación de «' + productName + '»?')) {
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
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-presentation' }));

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

    {{-- Modal: escanear el código de barras con la cámara (reutiliza MaxxiBarcodeScanner) --}}
    <x-modal name="barcode-scanner" max-width="md">
        <div class="p-5 sm:p-6"
             x-init="$watch('show', value => {
                if (value) { setTimeout(() => window.startBarcodeScanner && window.startBarcodeScanner(), 150); }
                else { window.stopBarcodeScanner && window.stopBarcodeScanner(); }
             })">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-navy/5 text-maxxi-navy">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5v9M6.75 7.5v9M9.75 7.5v2.25M9.75 14.25v2.25M12.75 7.5v2.25M12.75 14.25v2.25M15.75 7.5v9M18.75 7.5v2.25M18.75 14.25v2.25M21 9v6" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">Escanear código de barras</h2>
                    <p class="mb-0 text-sm text-gray-600">Apunta la cámara al código del empaque. Al leerlo se colocará en el campo <span class="font-medium">Código de barras</span>.</p>
                </div>
            </div>

            <div id="barcode-reader" class="mt-4 aspect-video w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-900"></div>
            <p id="barcode-status" class="mt-2 mb-0 text-sm text-gray-500">Iniciando la cámara…</p>

            <div class="mt-6 flex justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'barcode-scanner')">
                    Cerrar
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Escáner de código de barras (cámara); el campo admite también escritura manual o lector USB --}}
    <script>
        (function () {
            var barcodeInput = document.getElementById('barcode');
            var barcodeScanOpen = document.getElementById('barcode-scan-open');
            var barcodeStatus = document.getElementById('barcode-status');

            // Traduce el error de la cámara a un mensaje claro en español.
            function barcodeErrorMessage(error) {
                var name = error && error.name;
                var message = (error && error.message) || '';

                if (name === 'NotAllowedError' || /permission|denied/i.test(message)) {
                    return 'Permiso de cámara denegado. Habilítalo en el navegador e inténtalo de nuevo.';
                }
                if (name === 'NotFoundError' || name === 'OverconstrainedError' || /no camera|not found/i.test(message)) {
                    return 'No se encontró una cámara disponible en este dispositivo.';
                }
                if (name === 'NotReadableError') {
                    return 'La cámara está en uso por otra aplicación. Ciérrala e inténtalo de nuevo.';
                }
                if (name === 'SecurityError') {
                    return 'El acceso a la cámara requiere una conexión segura (HTTPS). Puedes escribir el código en el campo.';
                }
                return 'No se pudo iniciar la cámara. Escribe el código directamente en el campo.';
            }

            function setBarcodeStatus(message, isError) {
                if (!barcodeStatus) { return; }
                barcodeStatus.textContent = message;
                barcodeStatus.classList.toggle('text-red-600', !!isError);
                barcodeStatus.classList.toggle('text-gray-500', !isError);
            }

            window.startBarcodeScanner = function () {
                setBarcodeStatus('Iniciando la cámara…', false);

                if (!window.MaxxiBarcodeScanner) {
                    setBarcodeStatus('El escáner aún no está listo. Intenta de nuevo en un momento.', true);
                    return;
                }

                window.MaxxiBarcodeScanner.start('barcode-reader', function (decodedText) {
                    // Éxito: se detiene la cámara, se cierra el modal y se coloca el código en el campo.
                    window.stopBarcodeScanner();
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'barcode-scanner' }));

                    if (barcodeInput) {
                        barcodeInput.value = (decodedText || '').trim();
                        barcodeInput.focus();
                    }
                }, function (error) {
                    setBarcodeStatus(barcodeErrorMessage(error), true);
                });
            };

            window.stopBarcodeScanner = function () {
                if (window.MaxxiBarcodeScanner) { window.MaxxiBarcodeScanner.stop(); }
            };

            if (barcodeScanOpen) {
                barcodeScanOpen.addEventListener('click', function () {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'barcode-scanner' }));
                });
            }
        })();
    </script>

    @once
        @vite('resources/js/barcode-scanner.js')
    @endonce
</x-layouts.sistema>
