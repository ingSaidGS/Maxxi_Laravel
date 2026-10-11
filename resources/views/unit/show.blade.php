<x-layouts.sistema title="Detalle de unidad">
    <h1>Unidad #{{ $unit->id }}</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <dl>
        <dt>Nombre</dt>
        <dd>{{ $unit->name }}</dd>

        <dt>Símbolo</dt>
        <dd>{{ $unit->symbol }}</dd>

        <dt>Activa</dt>
        <dd>{{ $unit->active ? 'Sí' : 'No' }}</dd>

        <dt>Creada</dt>
        <dd>{{ $unit->created_at }}</dd>

        <dt>Actualizada</dt>
        <dd>{{ $unit->updated_at }}</dd>
    </dl>

    <p>
        <a href="{{ route('units.edit', $unit) }}">Editar</a>
        <a href="{{ route('units.index') }}">Volver al listado</a>
    </p>
</x-layouts.sistema>
