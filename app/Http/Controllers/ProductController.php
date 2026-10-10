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
            'active' => ['boolean'],
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Fija el stock del producto a partir de la presentación de compra activa.
     *
     * El stock se guarda en unidad base: paquetes * factor de conversión de la
     * presentación de compra + unidades sueltas.
     */
    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $purchasePresentation = $product->presentations()
            ->where('purchase_enable', true)
            ->where('active', true)
            ->first();

        if ($purchasePresentation === null) {
            return redirect()
                ->route('products.show', $product)
                ->withErrors(['stock' => 'El producto no tiene una presentación de compra activa.']);
        }

        $validated = $request->validate([
            'stock_packages' => ['nullable', 'integer', 'min:0'],
            'stock_loose' => ['nullable', 'integer', 'min:0'],
        ]);

        $conversionFactor = max(1, (int) $purchasePresentation->conversion_factor);
        $packages = (int) ($validated['stock_packages'] ?? 0);
        $loose = (int) ($validated['stock_loose'] ?? 0);

        $product->update(['stock' => $packages * $conversionFactor + $loose]);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Stock actualizado correctamente.');
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
