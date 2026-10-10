<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva presentación</title>
</head>
<body>
    <h1>Nueva presentación</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('product-presentations.store') }}" method="POST">
        @csrf

        <p>
            <strong>Producto:</strong> {{ $product->name }}
            — la presentación se creará para este producto.
            <input type="hidden" name="product_id" value="{{ $product->id }}">
        </p>

        <p>
            <label for="unit_id">Unidad</label>
            <select id="unit_id" name="unit_id" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                        {{ $unit->name }} ({{ $unit->symbol }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label for="conversion_factor">Factor de conversión</label>
            <input type="number" id="conversion_factor" name="conversion_factor"
                   value="{{ old('conversion_factor', 1) }}" step="1" min="0" required>
        </p>

        <p id="sale-price-wrapper" hidden>
            <label for="sale_price">Precio de venta</label>
            <input type="number" id="sale_price" name="sale_price"
                   value="{{ old('sale_price') }}" step="0.1" min="0">
        </p>

        <p>
            <input type="hidden" name="purchase_enable" value="0">
            <label>
                <input type="checkbox" name="purchase_enable" value="1" @checked(old('purchase_enable'))>
                Compra habilitada
            </label>
        </p>

        <p>
            <input type="hidden" name="sale_enable" value="0">
            <label>
                <input type="checkbox" name="sale_enable" value="1" @checked(old('sale_enable', true))>
                Venta habilitada
            </label>
        </p>

        <p id="reference-purchase-cost-wrapper" hidden>
            <label for="reference_purchase_cost">Costo de compra de referencia</label>
            <input type="number" id="reference_purchase_cost" name="reference_purchase_cost"
                   value="{{ old('reference_purchase_cost') }}" step="0.01" min="0" @required(old('purchase_enable'))>
        </p>

        <p>
            <label for="desired_profit">Ganancia deseada</label>
            <input type="number" id="desired_profit" name="desired_profit"
                   value="{{ old('desired_profit') }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="barcode">Código de barras (opcional)</label>
            <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" maxlength="20">
        </p>

        <p>
            <button type="submit">Guardar</button>
            <a href="{{ route('products.show', $product) }}">Cancelar</a>
        </p>
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
            saleInput.placeholder = salePriceLocked ? 'Requiere una presentación de compra activa' : '';
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
</body>
</html>
