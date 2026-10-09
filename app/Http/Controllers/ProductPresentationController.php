<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPresentationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $product = Product::findOrFail($request->integer('product_id'));
        $units = Unit::orderBy('name')->get();

        return view('product-presentation.create', compact('product', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
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

        return redirect()
            ->route('products.show', $presentation->product_id)
            ->with('status', 'Presentación creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductPresentation $productPresentation): View
    {
        $productPresentation->load(['product', 'unit']);

        return view('product-presentation.show', ['presentation' => $productPresentation]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductPresentation $productPresentation): View
    {
        $productPresentation->load('product');
        $units = Unit::orderBy('name')->get();

        return view('product-presentation.edit', [
            'presentation' => $productPresentation,
            'units' => $units,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductPresentation $productPresentation): RedirectResponse
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

        return redirect()
            ->route('product-presentations.show', $productPresentation)
            ->with('status', 'Presentación actualizada correctamente.');
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(ProductPresentation $productPresentation): RedirectResponse
    {
        $productPresentation->update(['active' => false]);

        return redirect()
            ->route('products.show', $productPresentation->product_id)
            ->with('status', 'Presentación dada de baja correctamente.');
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(ProductPresentation $productPresentation): RedirectResponse
    {
        $productPresentation->update(['active' => true]);

        return redirect()
            ->route('products.show', $productPresentation->product_id)
            ->with('status', 'Presentación reactivada correctamente.');
    }
}
