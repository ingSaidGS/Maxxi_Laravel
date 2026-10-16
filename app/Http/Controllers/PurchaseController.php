<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    /**
     * Display a listing of the resource.
     *
     * El listado se filtra en el servidor por fechas ("date_from"/"date_to",
     * incluyentes y en formato "Y-m-d") y término de búsqueda. La búsqueda
     * compara sin distinguir mayúsculas ("ilike" de PostgreSQL) contra el
     * proveedor, el número de comprobante y el nombre del usuario.
     *
     * Sin ningún filtro activo se muestran las compras de hoy por defecto; en
     * cuanto hay algún filtro, se busca en todo el histórico para que sea
     * posible encontrar compras anteriores aunque hoy no haya compras. El rango
     * de fechas acota siempre el resultado cuando viene indicado.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 15);

        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $search = $request->query('search', $request->query('q'));

        $filters = [
            'date_from' => $this->sanitizeDateFilter($request->query('date_from')),
            'date_to' => $this->sanitizeDateFilter($request->query('date_to')),
            'search' => is_string($search) ? trim($search) : '',
        ];

        // Resumen sobre todo el resultado filtrado (no solo la página actual).
        $summary = $this->filteredPurchases($filters)
            ->selectRaw('count(*) as purchases_count, coalesce(sum(total), 0) as sum_total')
            ->first();

        $purchases = $this->filteredPurchases($filters)
            ->with('user')
            ->orderByDesc('purchased_at')
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'count' => (int) ($summary->purchases_count ?? 0),
            'total' => (float) ($summary->sum_total ?? 0),
        ];

        return view('purchase.index', compact('purchases', 'filters', 'summary'));
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
     * Construye la consulta del listado de compras aplicando los filtros.
     *
     * - Solo se limita al día de hoy cuando no hay ningún filtro activo (es el
     *   valor por defecto del listado); con cualquier filtro activo se recorre
     *   el histórico completo y el rango de fechas acota si viene indicado.
     * - "date_from" filtra desde el inicio del día y "date_to" hasta el final
     *   del día (ambos son inclusivos).
     * - La búsqueda usa "ilike" (PostgreSQL) para ignorar mayúsculas/minúsculas.
     *
     * @param  array{date_from: ?string, date_to: ?string, search: string}  $filters
     * @return Builder<Purchase>
     */
    protected function filteredPurchases(array $filters): Builder
    {
        $query = Purchase::query();

        $hasFilter = $filters['date_from'] !== null
            || $filters['date_to'] !== null
            || $filters['search'] !== '';

        if (! $hasFilter) {
            $today = now()->startOfDay();

            $query->where('purchased_at', '>=', $today)
                ->where('purchased_at', '<', $today->copy()->addDay());
        } else {
            if ($filters['date_from'] !== null) {
                $query->where('purchased_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            }

            if ($filters['date_to'] !== null) {
                $query->where('purchased_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
            }
        }

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('supplier', 'ilike', $like)
                    ->orWhere('voucher_number', 'ilike', $like)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'ilike', $like));
            });
        }

        return $query;
    }

    /**
     * Normaliza un filtro de fecha recibido por query string.
     *
     * Devuelve la fecha en formato "Y-m-d" o null si el valor está vacío o no
     * es una fecha válida; un parámetro corrupto no debe romper el listado.
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
