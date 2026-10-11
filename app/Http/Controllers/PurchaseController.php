<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $purchases = Purchase::with('user')->orderByDesc('purchased_at')->get();

        return view('purchase.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Se cargan las presentaciones habilitadas para compra y activas para armar
     * el carrito. El precio unitario de cada línea es el costo de referencia de
     * la presentación.
     */
    public function create(): View
    {
        $presentations = $this->purchasablePresentations();

        return view('purchase.create', compact('presentations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * La cabecera y las líneas se registran en una única transacción: se guarda
     * la compra, se incrementa el stock de cada producto y se reaplica el costo
     * de referencia de la presentación comprada.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchases->create($request->dataForPersistence(), $request->user()->id);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', 'Compra registrada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase): View
    {
        $purchase->load([
            'user',
            'purchaseDetails.presentation.product.baseUnit',
            'purchaseDetails.presentation.unit',
        ]);

        return view('purchase.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase): View
    {
        return view('purchase.edit', compact('purchase'));
    }

    /**
     * Update the specified resource in storage.
     *
     * Solo se actualiza la cabecera; las líneas no se modifican (la compra es
     * histórica) y el total se recalcula desde los detalles existentes.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->purchases->updateHeader($purchase, $request->dataForPersistence());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', 'Compra actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Presentaciones disponibles para compra, listas para el formulario.
     *
     * Se identifica cada presentación por su unidad + factor de conversión (el
     * "sizeLabel" es la equivalencia legible, p. ej. "x24 kg") y el importe que
     * se expone al formulario es el costo de referencia de la presentación.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function purchasablePresentations(): Collection
    {
        return ProductPresentation::with(['product.baseUnit', 'unit'])
            ->where('purchase_enable', true)
            ->where('active', true)
            ->orderBy('id')
            ->get()
            ->map(function (ProductPresentation $presentation): array {
                $baseUnitSymbol = $presentation->product?->baseUnit?->symbol;
                $sizeLabel = 'x'.$presentation->conversion_factor.($baseUnitSymbol ? ' '.$baseUnitSymbol : '');

                return [
                    'id' => $presentation->id,
                    'label' => trim(
                        ($presentation->product?->name ?? '—').' — '.($presentation->unit?->name ?? '')
                        .' ('.$sizeLabel.')'
                    ),
                    'cost' => (float) ($presentation->reference_purchase_cost ?? 0),
                    'stock' => (int) ($presentation->product?->stock ?? 0),
                    'barcode' => $presentation->barcode,
                    'productId' => $presentation->product_id,
                    'productName' => $presentation->product?->name,
                    'unitName' => $presentation->unit?->name,
                    'unitSymbol' => $presentation->unit?->symbol,
                    'conversionFactor' => (int) $presentation->conversion_factor,
                    'sizeLabel' => $sizeLabel,
                    'baseUnitName' => $presentation->product?->baseUnit?->name,
                    'baseUnitSymbol' => $baseUnitSymbol,
                ];
            })
            ->values();
    }
}
