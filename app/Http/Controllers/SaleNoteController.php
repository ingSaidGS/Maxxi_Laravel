<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Http\Requests\SaleNote\DeliverSaleNoteRequest;
use App\Http\Requests\SaleNote\RefreshSaleNotePricesRequest;
use App\Http\Requests\SaleNote\StoreSaleNoteDetailRequest;
use App\Http\Requests\SaleNote\StoreSaleNoteRequest;
use App\Http\Requests\SaleNote\UpdateSaleNoteDetailQuantityRequest;
use App\Http\Requests\SaleNote\UpdateSaleNoteRequest;
use App\Models\ProductPresentation;
use App\Models\SaleNote;
use App\Models\SaleNoteDetail;
use App\Services\ProfitService;
use App\Services\SaleNoteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SaleNoteController extends Controller
{
    use RemembersIndexUrl;

    /**
     * Clave de sesión con la última URL del listado (página, fechas, estado,
     * búsqueda y cantidad por página), para volver a él sin perder la posición.
     * Ver RemembersIndexUrl.
     */
    public const INDEX_URL_SESSION_KEY = 'sale-notes.index_url';

    /**
     * Ruta del listado cuando no hay URL guardada.
     */
    public const INDEX_ROUTE = 'sale-notes.index';

    public function __construct(private readonly SaleNoteService $saleNotes) {}

    /**
     * Display a listing of the resource.
     *
     * El listado se filtra en el servidor por fechas ("date_from"/"date_to",
     * incluyentes y en formato "Y-m-d"), estado y término de búsqueda. La
     * búsqueda compara sin distinguir mayúsculas ("ilike" de PostgreSQL) contra
     * el cliente, el teléfono y el nombre del usuario.
     *
     * Sin ningún filtro activo se muestran las notas de hoy por defecto (igual
     * que en ventas); en cuanto hay algún filtro, se busca en todo el histórico
     * para poder encontrar notas anteriores aunque hoy no haya. El rango de
     * fechas acota siempre el resultado cuando viene indicado.
     */
    public function index(Request $request, ProfitService $profits): View|RedirectResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $search = $request->query('search', $request->query('q'));

        $filters = [
            'date_from' => $this->sanitizeDateFilter($request->query('date_from')),
            'date_to' => $this->sanitizeDateFilter($request->query('date_to')),
            'status' => in_array($request->query('status'), ['pendiente', 'entregada', 'anulada'], true) ? $request->query('status') : null,
            'search' => is_string($search) ? trim($search) : '',
        ];

        // Resumen sobre todo el resultado filtrado (no solo la página actual).
        $summary = $this->filteredSaleNotes($filters)
            ->selectRaw(
                'count(*) as notes_count, coalesce(sum(total), 0) as sum_total,'
                .' coalesce(sum(case when status = ? then 1 else 0 end), 0) as pending_count',
                ['pendiente']
            )
            ->first();

        // Las líneas (de la nota y de su venta) se cargan solo para la página
        // actual, para calcular la ganancia bruta sin consultas por fila.
        $saleNotes = $this->filteredSaleNotes($filters)
            ->with([
                'user',
                'sale.saleDetails.presentation.product',
                'saleNoteDetails.presentation.product',
            ])
            ->orderByDesc('noted_at')
            ->paginate($perPage)
            ->withQueryString();

        if ($redirect = $this->rememberIndexUrl($request, $saleNotes)) {
            return $redirect;
        }

        $summary = [
            'count' => (int) ($summary->notes_count ?? 0),
            'total' => (float) ($summary->sum_total ?? 0),
            'pending' => (int) ($summary->pending_count ?? 0),
        ];

        // Ganancia bruta por nota (null en anuladas); ver ProfitService::forSaleNote().
        $noteProfits = $saleNotes->getCollection()
            ->mapWithKeys(fn ($saleNote) => [$saleNote->id => $profits->forSaleNote($saleNote)]);

        return view('sale-note.index', compact('saleNotes', 'filters', 'summary', 'noteProfits'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Se cargan las presentaciones habilitadas para venta, activas y cuyo producto
     * esté activo para armar el carrito. El precio de cada línea es el precio de
     * venta de la presentación.
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
     *
     * Además de las líneas, se cargan las presentaciones vendibles para el modal
     * "Agregar producto" (mismo listado que el alta de notas).
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

        $presentations = $this->saleablePresentations();

        // Precios del catálogo que cambiaron desde que se tomó el pedido (solo
        // en notas pendientes); la vista ofrece actualizarlos.
        $priceChanges = $this->saleNotes->priceChanges($saleNote);

        return view('sale-note.show', compact('saleNote', 'presentations', 'priceChanges'));
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
            ->to($this->indexUrl())
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
     * Actualiza al precio vigente del catálogo las líneas de una nota pendiente
     * cuyo precio cambió desde que se tomó el pedido, y recalcula el total.
     */
    public function refreshPrices(RefreshSaleNotePricesRequest $request, SaleNote $saleNote): RedirectResponse
    {
        $updated = $this->saleNotes->refreshPrices($saleNote, $request->dataForPersistence());

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', $updated === 1
                ? 'Se actualizó el precio de 1 producto. El total de la nota se recalculó.'
                : "Se actualizaron los precios de {$updated} productos. El total de la nota se recalculó.");
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
     * Agrega una presentación a una nota pendiente.
     *
     * Reutiliza la misma validación de presentaciones vendibles que el alta de
     * notas. Si la presentación ya está activa en la nota, la cantidad se suma a
     * esa línea (ver SaleNoteService::addDetail()).
     */
    public function storeDetail(StoreSaleNoteDetailRequest $request, SaleNote $saleNote): RedirectResponse
    {
        $data = $request->dataForPersistence();

        $this->saleNotes->addDetail($saleNote, $data['presentation_id'], $data['quantity']);

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Producto agregado a la nota.');
    }

    /**
     * Actualiza la cantidad de una línea de la nota y recalcula el total.
     *
     * Solo se permite en notas pendientes: el estado se valida en el servicio
     * (ensurePending()) y la línea debe pertenecer a la nota.
     */
    public function updateDetailQuantity(
        UpdateSaleNoteDetailQuantityRequest $request,
        SaleNote $saleNote,
        SaleNoteDetail $saleNoteDetail
    ): RedirectResponse {
        $this->ensureDetailBelongsToNote($saleNote, $saleNoteDetail);

        $this->saleNotes->updateDetailQuantity($saleNoteDetail, $request->dataForPersistence()['quantity']);

        return redirect()
            ->route('sale-notes.show', $saleNote)
            ->with('status', 'Cantidad actualizada correctamente.');
    }

    /**
     * Construye la consulta del listado de notas de venta aplicando los filtros.
     *
     * Mismo criterio que ventas: sin ningún filtro se acota a las notas de hoy
     * (desde el inicio del día hasta el inicio del día siguiente); con cualquier
     * filtro se busca en todo el histórico. "date_from" filtra desde el inicio
     * del día y "date_to" hasta el final del día (ambos inclusivos). La búsqueda
     * usa "ilike" (PostgreSQL) para ignorar mayúsculas/minúsculas.
     *
     * @param  array{date_from: ?string, date_to: ?string, status: ?string, search: string}  $filters
     * @return Builder<SaleNote>
     */
    protected function filteredSaleNotes(array $filters): Builder
    {
        $query = SaleNote::query();

        $hasFilter = $filters['date_from'] !== null
            || $filters['date_to'] !== null
            || $filters['status'] !== null
            || $filters['search'] !== '';

        if (! $hasFilter) {
            $today = now()->startOfDay();

            $query->where('noted_at', '>=', $today)
                ->where('noted_at', '<', $today->copy()->addDay());
        } else {
            if ($filters['date_from'] !== null) {
                $query->where('noted_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            }

            if ($filters['date_to'] !== null) {
                $query->where('noted_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
            }
        }

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('customer_name', 'ilike', $like)
                    ->orWhere('customer_phone', 'ilike', $like)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'ilike', $like));
            });
        }

        return $query;
    }

    /**
     * Normaliza un filtro de fecha recibido por query string.
     *
     * Devuelve la fecha en formato "Y-m-d" o null si el valor está vacío o no es
     * una fecha válida; un parámetro corrupto no debe romper el listado.
     */
    protected function sanitizeDateFilter(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $value : null;
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
     * Solo se incluyen presentaciones habilitadas para venta, activas y cuyo
     * producto también esté activo. Se identifica cada presentación por su unidad +
     * factor de conversión (el "sizeLabel" es la equivalencia legible, p. ej.
     * "x24 kg") y el importe que se expone al formulario es el precio de venta de
     * la presentación.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function saleablePresentations(): Collection
    {
        return ProductPresentation::with(['product.baseUnit', 'unit'])
            ->where('sale_enable', true)
            ->where('active', true)
            ->whereHas('product', function ($query) {
                $query->where('active', true);
            })
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
