<x-layouts.sistema title="Editar unidad">
    <h1>Editar unidad</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name', $unit->name) }}" maxlength="10" required>
        </p>

        <p>
            <label for="symbol">Símbolo</label>
            <input type="text" id="symbol" name="symbol" value="{{ old('symbol', $unit->symbol) }}" maxlength="3" required>
        </p>

        <p>
            <button type="submit">Actualizar</button>
            <a href="{{ route('units.index') }}">Cancelar</a>
        </p>
    </form>
</x-layouts.sistema>
