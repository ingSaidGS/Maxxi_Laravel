<x-layouts.sistema title="Unidades">
    <h1>Unidades</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>
        <a href="{{ route('units.create') }}">Nueva unidad</a>
    </p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Símbolo</th>
                <th>Activa</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($units as $unit)
                <tr>
                    <td>{{ $unit->id }}</td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->symbol }}</td>
                    <td>{{ $unit->active ? 'Sí' : 'No' }}</td>
                    <td>
                        <a href="{{ route('units.show', $unit) }}">Ver</a>
                        <a href="{{ route('units.edit', $unit) }}">Editar</a>

                        @if ($unit->active)
                            <form action="{{ route('units.destroy', $unit) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Dar de baja</button>
                            </form>
                        @else
                            <form action="{{ route('units.restore', $unit) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit">Reactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay unidades registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-layouts.sistema>
