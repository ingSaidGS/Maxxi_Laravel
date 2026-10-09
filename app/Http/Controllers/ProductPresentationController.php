<?php

namespace App\Http\Controllers;

use App\Models\ProductPresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPresentationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $presentations = ProductPresentation::orderBy('id')->get();

        return response()->json($presentations);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Sin vistas todavía: se devuelven los valores por defecto del formulario.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'product_id' => null,
            'unit_id' => null,
            'conversion_factor' => 1,
            'sale_price' => 0,
            'purchase_enable' => true,
            'sale_enable' => true,
            'barcode' => null,
            'active' => true,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversion_factor' => ['required', 'integer', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
            'purchase_enable' => ['boolean'],
            'sale_enable' => ['boolean'],
            'barcode' => ['nullable', 'string', 'max:20'],
            'active' => ['boolean'],
        ]);

        $presentation = ProductPresentation::create($validated);

        return response()->json($presentation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductPresentation $productPresentation): JsonResponse
    {
        return response()->json($productPresentation);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Sin vistas todavía: se devuelven los datos actuales de la presentación.
     */
    public function edit(ProductPresentation $productPresentation): JsonResponse
    {
        return response()->json($productPresentation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductPresentation $productPresentation): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversion_factor' => ['required', 'integer', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
            'purchase_enable' => ['boolean'],
            'sale_enable' => ['boolean'],
            'barcode' => ['nullable', 'string', 'max:20'],
            'active' => ['boolean'],
        ]);

        $productPresentation->update($validated);

        return response()->json($productPresentation);
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(ProductPresentation $productPresentation): JsonResponse
    {
        $productPresentation->update(['active' => false]);

        return response()->json($productPresentation);
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(ProductPresentation $productPresentation): JsonResponse
    {
        $productPresentation->update(['active' => true]);

        return response()->json($productPresentation);
    }
}
