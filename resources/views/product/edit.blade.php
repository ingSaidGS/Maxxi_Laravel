<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
</head>
<body>
    <h1>Editar producto</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('products.update', $product) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="20" required>
        </p>

        <p>
            <label for="base_unit_id">Unidad base</label>
            <select id="base_unit_id" name="base_unit_id" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(old('base_unit_id', $product->base_unit_id) == $unit->id)>
                        {{ $unit->name }} ({{ $unit->symbol }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <button type="submit">Actualizar</button>
            <a href="{{ route('products.index') }}">Cancelar</a>
        </p>
    </form>
</body>
</html>
