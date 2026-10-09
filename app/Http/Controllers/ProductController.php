<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $products = Product::with('baseUnit')->orderBy('name')->get();

        return view('product.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $units = Unit::orderBy('name')->get();

        return view('product.create', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'reference_purchase_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'active' => ['boolean'],
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): View
    {
        $product->load(['baseUnit', 'presentations.unit']);

        return view('product.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $units = Unit::orderBy('name')->get();

        return view('product.edit', compact('product', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'reference_purchase_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'active' => ['boolean'],
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['active' => false]);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto dado de baja correctamente.');
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(Product $product): RedirectResponse
    {
        $product->update(['active' => true]);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto reactivado correctamente.');
    }
}
