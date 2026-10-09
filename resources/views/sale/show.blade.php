<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de venta</title>
</head>
<body>
    <h1>Venta #{{ $sale->id }}</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <dl>
        <dt>Cliente</dt>
        <dd>{{ $sale->customer_name }}</dd>

        <dt>Teléfono</dt>
        <dd>{{ $sale->customer_phone }}</dd>

        <dt>Fecha de venta</dt>
        <dd>{{ $sale->sold_at }}</dd>

        <dt>Total</dt>
        <dd>{{ $sale->total }}</dd>

        <dt>Estado</dt>
        <dd>{{ $sale->status }}</dd>

        <dt>Descuento</dt>
        <dd>{{ $sale->sale_discount }}</dd>

        <dt>Efectivo</dt>
        <dd>{{ $sale->cash }}</dd>

        <dt>QR</dt>
        <dd>{{ $sale->qr }}</dd>

        <dt>Fiado (deuda)</dt>
        <dd>{{ $sale->debt }}</dd>

        <dt>Usuario (cajero)</dt>
        <dd>{{ $sale->user?->name ?? '—' }}</dd>

        <dt>Creada</dt>
        <dd>{{ $sale->created_at }}</dd>

        <dt>Actualizada</dt>
        <dd>{{ $sale->updated_at }}</dd>
    </dl>

    <h2>Detalle de la venta</h2>

    @if ($sale->saleDetails->isEmpty())
        <p>Esta venta no tiene detalles registrados.</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Factor</th>
                    <th>Vendible</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->saleDetails as $detail)
                    <tr>
                        <td>{{ $detail->presentation?->product?->name ?? '—' }}</td>
                        <td>{{ $detail->presentation?->unit?->name ?? '—' }}</td>
                        <td>{{ $detail->quantity }}</td>
                        <td>{{ $detail->conversion_factor }}</td>
                        <td>{{ $detail->sale_enable ? 'Sí' : 'No' }}</td>
                        <td>{{ $detail->subtotal }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>
        <a href="{{ route('sales.edit', $sale) }}">Editar</a>
        <a href="{{ route('sales.index') }}">Volver al listado</a>
    </p>
</body>
</html>
