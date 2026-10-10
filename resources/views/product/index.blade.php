<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
</head>
<body>
    <h1>Productos</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>
        <a href="{{ route('products.create') }}">Nuevo producto</a>
    </p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Unidad base</th>
                <th>Stock</th>
                <th>Activo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>
                        @if ($product->baseUnit)
                            {{ $product->baseUnit->name }} ({{ $product->baseUnit->symbol }})
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $product->stock }}</td>
                    <td>{{ $product->active ? 'Sí' : 'No' }}</td>
                    <td>
                        <a href="{{ route('products.show', $product) }}">Ver</a>
                        <a href="{{ route('products.edit', $product) }}">Editar</a>

                        @if ($product->active)
                            <form action="{{ route('products.destroy', $product) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Dar de baja</button>
                            </form>
                        @else
                            <form action="{{ route('products.restore', $product) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit">Reactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay productos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
