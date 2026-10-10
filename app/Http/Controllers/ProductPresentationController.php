<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPresentation\StoreProductPresentationRequest;
use App\Http\Requests\ProductPresentation\UpdateProductPresentationRequest;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use App\Services\ProductPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPresentationController extends Controller
{
    public function __construct(private readonly ProductPresentationService $service) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $product = Product::findOrFail($request->integer('product_id'));
        $units = Unit::orderBy('name')->get();
        $purchaseCostPerBaseUnit = $this->service->purchaseCostPerBaseUnit($product);

        return view('product-presentation.create', compact('product', 'units', 'purchaseCostPerBaseUnit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductPresentationRequest $request): RedirectResponse
    {
        $presentation = $this->service->create($request->dataForPersistence());

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
        $purchaseCostPerBaseUnit = $this->service->purchaseCostPerBaseUnit($productPresentation->product);

        return view('product-presentation.edit', [
            'presentation' => $productPresentation,
            'units' => $units,
            'purchaseCostPerBaseUnit' => $purchaseCostPerBaseUnit,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductPresentationRequest $request, ProductPresentation $productPresentation): RedirectResponse
    {
        $this->service->update($productPresentation, $request->dataForPersistence());

        return redirect()
            ->route('product-presentations.show', $productPresentation)
            ->with('status', 'Presentación actualizada correctamente.');
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia `active` a false.
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
     */
    public function restore(ProductPresentation $productPresentation): RedirectResponse
    {
        $this->service->restore($productPresentation);

        return redirect()
            ->route('products.show', $productPresentation->product_id)
            ->with('status', 'Presentación reactivada correctamente.');
    }
}
