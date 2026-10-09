<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas</title>
</head>
<body>
    <h1>Ventas</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>
        <a href="{{ route('sales.create') }}">Nueva venta</a>
    </p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Usuario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->customer_name }}</td>
                    <td>{{ $sale->customer_phone }}</td>
                    <td>{{ $sale->sold_at }}</td>
                    <td>{{ $sale->total }}</td>
                    <td>{{ $sale->status }}</td>
                    <td>{{ $sale->user?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('sales.show', $sale) }}">Ver</a>
                        <a href="{{ route('sales.edit', $sale) }}">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay ventas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
