<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'symbol' => ['required', 'string', 'max:3'],
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'symbol' => ['required', 'string', 'max:3'],
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
}
