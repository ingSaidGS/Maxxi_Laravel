<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = Product::orderBy('name')->get();

        return response()->json($products);
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
            'base_unit_id' => null,
            'reference_purchase_cost' => 0,
            'active' => true,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'reference_purchase_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'active' => ['boolean'],
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Sin vistas todavía: se devuelven los datos actuales del producto.
     */
    public function edit(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'reference_purchase_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'active' => ['boolean'],
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->update(['active' => false]);

        return response()->json($product);
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(Product $product): JsonResponse
    {
        $product->update(['active' => true]);

        return response()->json($product);
    }
}
