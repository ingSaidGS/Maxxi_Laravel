<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar venta</title>
</head>
<body>
    <h1>Editar venta</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('sales.update', $sale) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="customer_name">Cliente (opcional)</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}" maxlength="20">
        </p>

        <p>
            <label for="customer_phone">Teléfono (opcional)</label>
            <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $sale->customer_phone) }}" maxlength="10">
        </p>

        <p>
            <label for="sold_at">Fecha de venta</label>
            <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at', $sale->sold_at?->format('Y-m-d\TH:i')) }}" required>
        </p>

        <p>
            <small>El estado (pagada/fiada) y el cajero se determinan automáticamente al guardar.</small>
        </p>

        <p>
            <strong>Total (calculado): {{ $sale->total }}</strong>
            <small>Se recalcula a partir de los detalles y el descuento al guardar.</small>
        </p>

        <p>
            <label for="sale_discount">Descuento</label>
            <input type="number" id="sale_discount" name="sale_discount" value="{{ old('sale_discount', $sale->sale_discount) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="cash">Efectivo</label>
            <input type="number" id="cash" name="cash" value="{{ old('cash', $sale->cash) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="qr">QR</label>
            <input type="number" id="qr" name="qr" value="{{ old('qr', $sale->qr) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="debt">Fiado (deuda)</label>
            <input type="number" id="debt" name="debt" value="{{ old('debt', $sale->debt) }}" step="0.1" min="0" required>
        </p>

        <p>
            <button type="submit">Actualizar</button>
            <a href="{{ route('sales.index') }}">Cancelar</a>
        </p>
    </form>
</body>
</html>
