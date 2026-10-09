<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo producto</title>
</head>
<body>
    <h1>Nuevo producto</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <p>
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="20" required>
        </p>

        <p>
            <label for="base_unit_id">Unidad base</label>
            <select id="base_unit_id" name="base_unit_id" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(old('base_unit_id') == $unit->id)>
                        {{ $unit->name }} ({{ $unit->symbol }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label for="reference_purchase_cost">Costo de compra de referencia</label>
            <input type="number" id="reference_purchase_cost" name="reference_purchase_cost"
                   value="{{ old('reference_purchase_cost') }}" step="0.01" min="0" required>
        </p>

        <p>
            <button type="submit">Guardar</button>
            <a href="{{ route('products.index') }}">Cancelar</a>
        </p>
    </form>
</body>
</html>
