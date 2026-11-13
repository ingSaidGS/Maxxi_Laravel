<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $units = Unit::orderBy('name')->get();

        return view('unit.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('unit.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->normalizeName($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10', 'unique:units,name'],
            'symbol' => ['required', 'string', 'max:3', 'unique:units,symbol'],
            'active' => ['boolean'],
        ]);

        Unit::create($validated);

        return redirect()
            ->route('units.index')
            ->with('status', 'Unidad creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit): View
    {
        return view('unit.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit): View
    {
        return view('unit.edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->normalizeName($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10', Rule::unique('units', 'name')->ignore($unit->id)],
            'symbol' => ['required', 'string', 'max:3', Rule::unique('units', 'symbol')->ignore($unit->id)],
            'active' => ['boolean'],
        ]);

        $unit->update($validated);

        return redirect()
            ->route('units.show', $unit)
            ->with('status', 'Unidad actualizada correctamente.');
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->update(['active' => false]);

        return redirect()
            ->route('units.index')
            ->with('status', 'Unidad dada de baja correctamente.');
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(Unit $unit): RedirectResponse
    {
        $unit->update(['active' => true]);

        return redirect()
            ->route('units.index')
            ->with('status', 'Unidad reactivada correctamente.');
    }

    /**
     * Normaliza el nombre antes de validar: sin espacios en los extremos, primera
     * letra en mayúscula y el resto en minúscula ("  KILOGRAMO " -> "Kilogramo").
     *
     * Se hace ANTES de la validación para que la regla "unique" compare el valor
     * que realmente se guardará (en PostgreSQL la comparación distingue mayúsculas,
     * así que "caja" no chocaría con "Caja" si se normalizara después).
     */
    private function normalizeName(Request $request): void
    {
        $name = $request->input('name');

        if (! is_string($name)) {
            return;
        }

        $request->merge(['name' => Str::ucfirst(Str::lower(trim($name)))]);
    }
}
