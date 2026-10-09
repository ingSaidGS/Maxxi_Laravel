<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de producto</title>
</head>
<body>
    <h1>Producto #{{ $product->id }}</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <dl>
        <dt>Nombre</dt>
        <dd>{{ $product->name }}</dd>

        <dt>Unidad base</dt>
        <dd>
            @if ($product->baseUnit)
                {{ $product->baseUnit->name }} ({{ $product->baseUnit->symbol }})
            @else
                —
            @endif
        </dd>

        <dt>Costo de compra de referencia</dt>
        <dd>{{ $product->reference_purchase_cost }}</dd>

        <dt>Activo</dt>
        <dd>{{ $product->active ? 'Sí' : 'No' }}</dd>

        <dt>Creado</dt>
        <dd>{{ $product->created_at }}</dd>

        <dt>Actualizado</dt>
        <dd>{{ $product->updated_at }}</dd>
    </dl>

    <p>
        <a href="{{ route('products.edit', $product) }}">Editar</a>
        <a href="{{ route('products.index') }}">Volver al listado</a>
    </p>

    <h2>Presentaciones</h2>

    <p>
        <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}">Nueva presentación</a>
    </p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Unidad</th>
                <th>Factor</th>
                <th>Precio venta</th>
                <th>Compra</th>
                <th>Venta</th>
                <th>Código de barras</th>
                <th>Activa</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($product->presentations as $presentation)
                <tr>
                    <td>{{ $presentation->id }}</td>
                    <td>
                        @if ($presentation->unit)
                            {{ $presentation->unit->name }} ({{ $presentation->unit->symbol }})
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $presentation->conversion_factor }}</td>
                    <td>{{ $presentation->sale_price }}</td>
                    <td>{{ $presentation->purchase_enable ? 'Sí' : 'No' }}</td>
                    <td>{{ $presentation->sale_enable ? 'Sí' : 'No' }}</td>
                    <td>{{ $presentation->barcode ?? '—' }}</td>
                    <td>{{ $presentation->active ? 'Sí' : 'No' }}</td>
                    <td>
                        <a href="{{ route('product-presentations.show', $presentation) }}">Ver</a>
                        <a href="{{ route('product-presentations.edit', $presentation) }}">Editar</a>

                        @if ($presentation->active)
                            <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Dar de baja</button>
                            </form>
                        @else
                            <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit">Reactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Este producto no tiene presentaciones.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
