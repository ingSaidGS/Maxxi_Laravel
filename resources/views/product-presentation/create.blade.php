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

        <p>
            <label for="sale_price">Precio de venta</label>
            <input type="number" id="sale_price" name="sale_price"
                   value="{{ old('sale_price') }}" step="0.1" min="0" required>
        </p>

        <p>
            <input type="hidden" name="purchase_enable" value="0">
            <label>
                <input type="checkbox" name="purchase_enable" value="1" @checked(old('purchase_enable', true))>
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

        <p>
            <label for="barcode">Código de barras (opcional)</label>
            <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" maxlength="20">
        </p>

        <p>
            <button type="submit">Guardar</button>
            <a href="{{ route('products.show', $product) }}">Cancelar</a>
        </p>
    </form>
</body>
</html>
