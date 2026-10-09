<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $units = Unit::orderBy('name')->get();

        return response()->json($units);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Sin vistas todavía: se devuelven los valores por defecto del formulario.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'name' => '',
            'symbol' => '',
            'active' => true,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'symbol' => ['required', 'string', 'max:3'],
            'active' => ['boolean'],
        ]);

        $unit = Unit::create($validated);

        return response()->json($unit, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit): JsonResponse
    {
        return response()->json($unit);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Sin vistas todavía: se devuelven los datos actuales de la unidad.
     */
    public function edit(Unit $unit): JsonResponse
    {
        return response()->json($unit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:10'],
            'symbol' => ['required', 'string', 'max:3'],
            'active' => ['boolean'],
        ]);

        $unit->update($validated);

        return response()->json($unit);
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $unit->update(['active' => false]);

        return response()->json($unit);
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(Unit $unit): JsonResponse
    {
        $unit->update(['active' => true]);

        return response()->json($unit);
    }
}
