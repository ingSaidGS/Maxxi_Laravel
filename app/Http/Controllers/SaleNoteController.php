<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleNote\DeliverSaleNoteRequest;
use App\Http\Requests\SaleNote\StoreSaleNoteRequest;
use App\Http\Requests\SaleNote\UpdateSaleNoteRequest;
use App\Models\ProductPresentation;
use App\Models\SaleNote;
use App\Models\SaleNoteDetail;
use App\Services\SaleNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SaleNoteController extends Controller
{
    public function __construct(private readonly SaleNoteService $saleNotes) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $saleNotes = SaleNote::with(['user', 'sale'])->orderByDesc('noted_at')->get();

        return view('sale-note.index', compact('saleNotes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Se cargan las presentaciones habilitadas para venta y activas para armar el
     * carrito. El precio de cada línea es el precio de venta de la presentación.
     */
    public function create(): View
    {
        $presentations = $this->saleablePresentations();

        return view('sale-note.create', compact('presentations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * La nota (preventa/pedido) NO mueve stock: se registra con sus líneas y su
     * total. El stock se descuenta recién al entregar (ver entregar()).
     */
    public function store(StoreSaleNoteRequest $request): RedirectResponse
    {
        $saleNote = $this->saleNotes->create($request->dataForPersistence(), $request->user()->id);

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Nota de venta registrada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SaleNote $saleNote): View
    {
        $saleNote->load([
            'user',
            'sale',
            'saleNoteDetails.presentation.product.baseUnit',
            'saleNoteDetails.presentation.product.presentations',
            'saleNoteDetails.presentation.unit',
        ]);

        return view('sale-note.show', compact('saleNote'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SaleNote $saleNote): View
    {
        $saleNote->load([
            'saleNoteDetails.presentation.product.baseUnit',
            'saleNoteDetails.presentation.unit',
        ]);

        return view('sale-note.edit', compact('saleNote'));
    }

    /**
     * Update the specified resource in storage.
     *
     * Solo se actualiza la cabecera; las líneas se quitan o reactivan de forma
     * individual y el total se recalcula desde las líneas activas.
     */
    public function update(UpdateSaleNoteRequest $request, SaleNote $saleNote): RedirectResponse
    {
        $this->saleNotes->updateHeader($saleNote, $request->dataForPersistence());

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Nota de venta actualizada correctamente.');
    }

    /**
     * Anula la nota (baja lógica vía estado).
     *
     * Las notas no se eliminan: pasan a "anulada" para conservar el histórico.
     * Solo se pueden anular notas pendientes.
     */
    public function destroy(SaleNote $saleNote): RedirectResponse
    {
        $this->saleNotes->cancel($saleNote);

        return redirect()
            ->route('sale-notes.index')
            ->with('status', 'Nota de venta anulada correctamente.');
    }

    /**
     * Entrega la nota y genera la venta.
     *
     * Al entregar se captura el pago (efectivo/QR), se descuenta el stock de las
     * líneas ACTIVAS, la nota pasa a "entregada" y se genera la venta
     * correspondiente. Se redirige al detalle de la venta generada.
     */
    public function entregar(DeliverSaleNoteRequest $request, SaleNote $saleNote): RedirectResponse
    {
        $sale = $this->saleNotes->deliver($saleNote, $request->dataForPersistence(), $request->user()->id);

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', "Nota #{$saleNote->id} entregada. Venta #{$sale->id} registrada.");
    }

    /**
     * Quita una línea de la nota (baja lógica).
     */
    public function deactivateDetail(SaleNote $saleNote, SaleNoteDetail $saleNoteDetail): RedirectResponse
    {
        $this->ensureDetailBelongsToNote($saleNote, $saleNoteDetail);

        $this->saleNotes->deactivateDetail($saleNoteDetail);

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Producto quitado de la nota.');
    }

    /**
     * Reactiva una línea previamente quitada de la nota.
     */
    public function restoreDetail(SaleNote $saleNote, SaleNoteDetail $saleNoteDetail): RedirectResponse
    {
        $this->ensureDetailBelongsToNote($saleNote, $saleNoteDetail);

        $this->saleNotes->restoreDetail($saleNoteDetail);

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Producto reactivado en la nota.');
    }

    /**
     * Verifica que la línea pertenezca a la nota indicada.
     */
    protected function ensureDetailBelongsToNote(SaleNote $saleNote, SaleNoteDetail $saleNoteDetail): void
    {
        abort_unless($saleNoteDetail->sale_note_id === $saleNote->id, 404);
    }

    /**
     * Presentaciones disponibles para venta, listas para el formulario.
     *
     * Se identifica cada presentación por su unidad + factor de conversión (el
     * "sizeLabel" es la equivalencia legible, p. ej. "x24 kg") y el importe que se
     * expone al formulario es el precio de venta de la presentación.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function saleablePresentations(): Collection
    {
        return ProductPresentation::with(['product.baseUnit', 'unit'])
            ->where('sale_enable', true)
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
                    'price' => (float) $presentation->sale_price,
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
