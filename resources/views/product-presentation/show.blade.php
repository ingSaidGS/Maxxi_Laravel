<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de presentación</title>
</head>
<body>
    <h1>Presentación #{{ $presentation->id }}</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <dl>
        <dt>Producto</dt>
        <dd>{{ $presentation->product?->name ?? '—' }}</dd>

        <dt>Unidad</dt>
        <dd>
            @if ($presentation->unit)
                {{ $presentation->unit->name }} ({{ $presentation->unit->symbol }})
            @else
                —
            @endif
        </dd>

        <dt>Factor de conversión</dt>
        <dd>{{ $presentation->conversion_factor }}</dd>

        <dt>Precio de venta</dt>
        <dd>{{ $presentation->sale_price ?? '—' }}</dd>

        <dt>Compra habilitada</dt>
        <dd>{{ $presentation->purchase_enable ? 'Sí' : 'No' }}</dd>

        <dt>Venta habilitada</dt>
        <dd>{{ $presentation->sale_enable ? 'Sí' : 'No' }}</dd>

        <dt>Costo de compra de referencia</dt>
        <dd>{{ $presentation->reference_purchase_cost ?? '—' }}</dd>

        <dt>Ganancia deseada</dt>
        <dd>{{ $presentation->desired_profit }}</dd>

        <dt>Código de barras</dt>
        <dd>{{ $presentation->barcode ?? '—' }}</dd>

        <dt>Activa</dt>
        <dd>{{ $presentation->active ? 'Sí' : 'No' }}</dd>

        <dt>Creada</dt>
        <dd>{{ $presentation->created_at }}</dd>

        <dt>Actualizada</dt>
        <dd>{{ $presentation->updated_at }}</dd>
    </dl>

    <p>
        <a href="{{ route('product-presentations.edit', $presentation) }}">Editar</a>
        <a href="{{ route('products.show', $presentation->product_id) }}">Volver al producto</a>
    </p>
</body>
</html>
