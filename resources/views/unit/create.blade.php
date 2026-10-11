<x-layouts.sistema title="Nueva unidad">
    <h1>Nueva unidad</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('units.store') }}" method="POST">
        @csrf

        <p>
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="10" required>
        </p>

        <p>
            <label for="symbol">Símbolo</label>
            <input type="text" id="symbol" name="symbol" value="{{ old('symbol') }}" maxlength="3" required>
        </p>

        <p>
            <button type="submit">Guardar</button>
            <a href="{{ route('units.index') }}">Cancelar</a>
        </p>
    </form>
</x-layouts.sistema>
