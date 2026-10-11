@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Nueva presentación">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Nueva presentación</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registra una presentación para el producto.</p>
        </div>

        <a href="{{ route('products.show', $product) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
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
    <form id="presentation-form" action="{{ route('product-presentations.store') }}" method="POST"
          class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf

        {{-- Contexto: producto al que pertenece la presentación --}}
        <div class="mb-5 flex items-center gap-3 rounded-lg border border-maxxi-navy/15 bg-maxxi-navy/5 px-4 py-3">
            <svg class="h-5 w-5 shrink-0 text-maxxi-navy" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>
            <div>
                <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Producto</p>
                <p class="mb-0 text-sm font-semibold text-gray-800">{{ $product->name }}</p>
            </div>
            <input type="hidden" name="product_id" value="{{ $product->id }}">
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="unit_id" class="mb-1 block text-sm font-medium text-gray-700">Unidad</label>
                <select id="unit_id" name="unit_id" required
                        @error('unit_id') aria-invalid="true" @enderror
                        class="block w-full rounded-lg shadow-sm focus:ring-2 @error('unit_id') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                            {{ $unit->name }} ({{ $unit->symbol }})
                        </option>
                    @endforeach
                </select>
                @error('unit_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="conversion_factor" class="mb-1 block text-sm font-medium text-gray-700">Factor de conversión</label>
                <input type="number" id="conversion_factor" name="conversion_factor"
                       value="{{ old('conversion_factor', 1) }}" step="1" min="0" required placeholder="Ej.: 24"
                       @error('conversion_factor') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('conversion_factor') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('conversion_factor')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <span class="mb-2 block text-sm font-medium text-gray-700">Tamaño</span>
                <div class="flex flex-wrap gap-x-8 gap-y-3 rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                    <label for="presentation_size_pequeno" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="radio" id="presentation_size_pequeno" name="presentation_size" value="pequeño" required
                               @checked(old('presentation_size') === 'pequeño')
                               class="h-4 w-4 border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Pequeño
                    </label>

                    <label for="presentation_size_grande" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="radio" id="presentation_size_grande" name="presentation_size" value="grande"
                               @checked(old('presentation_size') === 'grande')
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
                        <input type="checkbox" id="purchase_enable" name="purchase_enable" value="1" @checked(old('purchase_enable'))
                               class="h-4 w-4 rounded border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Compra habilitada
                    </label>

                    <label for="sale_enable" class="mb-0 inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="hidden" name="sale_enable" value="0">
                        <input type="checkbox" id="sale_enable" name="sale_enable" value="1" @checked(old('sale_enable', true))
                               class="h-4 w-4 rounded border-gray-300 text-maxxi-orange focus:ring-maxxi-orange">
                        Venta habilitada
                    </label>
                </div>
            </div>

            <div id="reference-purchase-cost-wrapper" class="sm:col-span-2" hidden>
                <label for="reference_purchase_cost" class="mb-1 block text-sm font-medium text-gray-700">Costo de compra de referencia</label>
                <input type="number" id="reference_purchase_cost" name="reference_purchase_cost"
                       value="{{ old('reference_purchase_cost') }}" step="0.01" min="0" @required(old('purchase_enable')) placeholder="Ej.: 120.00"
                       @error('reference_purchase_cost') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('reference_purchase_cost') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('reference_purchase_cost')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="sale-price-wrapper" class="sm:col-span-2" hidden>
                <label for="sale_price" class="mb-1 block text-sm font-medium text-gray-700">Precio de venta</label>
                <input type="number" id="sale_price" name="sale_price"
                       value="{{ old('sale_price') }}" step="0.1" min="0" placeholder="Ej.: 25.50"
                       @error('sale_price') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('sale_price') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('sale_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="desired_profit" class="mb-1 block text-sm font-medium text-gray-700">Ganancia deseada</label>
                <input type="number" id="desired_profit" name="desired_profit"
                       value="{{ old('desired_profit') }}" step="0.1" min="0" required placeholder="Ej.: 5.50"
                       @error('desired_profit') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('desired_profit') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('desired_profit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="barcode" class="mb-1 block text-sm font-medium text-gray-700">Código de barras <span class="font-normal text-gray-400">(opcional)</span></label>
                <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" maxlength="20" autocomplete="off" placeholder="Ej.: 7501234567890"
                       @error('barcode') aria-invalid="true" @enderror
                       class="block w-full rounded-lg shadow-sm focus:ring-2 @error('barcode') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                @error('barcode')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('products.show', $product) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Guardar
            </button>
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

        // Costo del producto por unidad mínima, tomado de la presentación de compra
        // que ya exista para este producto (null si no hay ninguna).
        const costPerBaseUnit = {{ $purchaseCostPerBaseUnit !== null ? json_encode($purchaseCostPerBaseUnit) : 'null' }};

        const calculateSalePrice = () => {
            if (costPerBaseUnit === null || !saleToggle.checked) {
                return;
            }

            const conversion = parseFloat(conversionInput.value) || 0;
            const profit = parseFloat(profitInput.value) || 0;
            const salePrice = costPerBaseUnit * conversion + profit;

            saleInput.value = (Math.round(salePrice * 10) / 10).toFixed(1);
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
            const salePriceLocked = costPerBaseUnit === null;

            saleWrapper.hidden = !saleEnabled;
            saleInput.disabled = !saleEnabled || salePriceLocked;
            saleInput.placeholder = salePriceLocked ? 'Requiere una presentación de compra activa' : 'Ej.: 25.50';
            if (!saleEnabled || salePriceLocked) {
                saleInput.value = '';
            }
        };

        purchaseToggle.addEventListener('change', syncFields);
        saleToggle.addEventListener('change', () => {
            syncFields();
            calculateSalePrice();
        });
        conversionInput.addEventListener('input', calculateSalePrice);
        profitInput.addEventListener('input', calculateSalePrice);

        syncFields();

        // Solo se precarga si no hay un valor previo (p. ej. tras un error de validación).
        if (saleInput.value.trim() === '') {
            calculateSalePrice();
        }
    </script>

    {{-- Modal: confirmar la creación de la presentación --}}
    <x-modal name="confirm-presentation" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Registrar esta presentación?</h2>
                    <p id="confirm-presentation-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-presentation')">
                    Cancelar
                </button>
                <button type="button" id="confirm-presentation-button" class="{{ $botones['primario'] }}">
                    Sí, registrar
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
            var productName = @json($product->name);
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
                    summary.textContent = 'Se registrará la presentación'
                        + (label ? ' «' + label + '»' : '')
                        + ' del producto «' + productName + '».';
                }

                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-presentation' }));
                    return;
                }

                // Respaldo por si el modal no está disponible.
                if (window.confirm('¿Registrar la presentación de «' + productName + '»?')) {
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
</x-layouts.sistema>
