<?php

namespace App\Http\Controllers;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $sales = Sale::with('user')->orderByDesc('sold_at')->get();

        return view('sale.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Se cargan las presentaciones con "sale_enable" activo para armar el carrito.
     */
    public function create(): View
    {
        $presentations = ProductPresentation::with(['product', 'unit'])
            ->where('sale_enable', true)
            ->where('active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (ProductPresentation $presentation): array => [
                'id' => $presentation->id,
                'label' => trim(
                    ($presentation->product?->name ?? '—').' — '.($presentation->unit?->name ?? '')
                    .' (x'.$presentation->conversion_factor.')'
                ),
                'price' => (float) $presentation->sale_price,
            ])
            ->values();

        return view('sale.create', compact('presentations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * El total se calcula como (suma de subtotales - descuento). El estado y el
     * cajero se derivan: "pagada" si efectivo + QR es mayor o igual al total,
     * "fiada" en caso contrario; el usuario se toma de la sesión autenticada.
     * Cuando la venta queda "fiada", el nombre y el teléfono del cliente son
     * obligatorios y la deuda (fio) se calcula como total - (efectivo + QR).
     *
     * La venta y el descuento de stock se ejecutan en una única transacción: si
     * algún producto no tiene stock suficiente, no se registra nada.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $details = $this->validatedDetails($request);

        $subtotals = 0.0;
        $requirements = [];

        foreach ($details as $detail) {
            $presentation = $detail['presentation'];
            $quantity = (int) $detail['quantity'];

            $subtotals += $quantity * (float) $presentation->sale_price;

            $requirements[] = [
                'product_id' => (int) $presentation->product_id,
                'base_quantity' => (int) $presentation->conversion_factor * $quantity,
            ];
        }

        $amounts = $this->resolveAmounts($subtotals, $validated);
        $this->ensureCustomerForFiada($amounts['status'], $validated);

        $validated['total'] = $amounts['total'];
        $validated['status'] = $amounts['status'];
        $validated['debt'] = $amounts['debt'];
        $validated['user_id'] = $request->user()->id;

        $sale = DB::transaction(function () use ($validated, $details, $requirements) {
            $sale = Sale::create($validated);

            foreach ($details as $detail) {
                $sale->saleDetails()->create(
                    $this->saleDetailAttributes($detail['presentation'], (int) $detail['quantity'])
                );
            }

            $this->stock->decrementForSale($requirements);

            return $sale;
        });

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', $this->saleStatusMessage('registrada', $amounts['status'], $amounts['debt']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): View
    {
        $sale->load(['user', 'saleDetails.presentation.product', 'saleDetails.presentation.unit']);

        return view('sale.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale): View
    {
        $subtotals = (float) $sale->saleDetails()->sum('subtotal');

        return view('sale.edit', compact('sale', 'subtotals'));
    }

    /**
     * Update the specified resource in storage.
     *
     * El total se recalcula a partir de los detalles existentes y, al igual que
     * en el alta, el estado y la deuda (fio) se derivan; si queda "fiada" el
     * nombre y el teléfono del cliente son obligatorios.
     */
    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $subtotals = (float) $sale->saleDetails()->sum('subtotal');
        $amounts = $this->resolveAmounts($subtotals, $validated);
        $this->ensureCustomerForFiada($amounts['status'], $validated);

        $validated['total'] = $amounts['total'];
        $validated['status'] = $amounts['status'];
        $validated['debt'] = $amounts['debt'];

        $sale->update($validated);

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', $this->saleStatusMessage('actualizada', $amounts['status'], $amounts['debt']));
    }

    /**
     * Las ventas no se eliminan.
     *
     * La tabla "sales" no tiene columna "active" y las ventas no deben borrarse,
     * por lo que se rechaza la operación sin modificar nada.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        return response()->json([
            'message' => 'Las ventas no se pueden eliminar.',
        ], 405);
    }

    /**
     * Reglas de validación según las columnas de "sales".
     *
     * "total" y "debt" no se validan aquí: se calculan en el servidor. El nombre
     * y el teléfono del cliente son opcionales; se exigen solo si la venta queda
     * "fiada" (ver ensureCustomerForFiada()).
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'];

        return [
            'customer_name' => ['nullable', 'string', 'max:20'],
            'customer_phone' => ['nullable', 'string', 'max:10'],
            'sold_at' => ['required', 'date'],
            'sale_discount' => $money,
            'cash' => $money,
            'qr' => $money,
        ];
    }

    /**
     * Deriva los importes de la venta a partir de los subtotales.
     *
     * "total" = subtotales - descuento; el estado se resuelve contra el monto
     * pagado (efectivo + QR) y la deuda "fiada" es lo que queda por pagar. En
     * ventas "pagada" la deuda siempre es cero.
     *
     * @param  array<string, mixed>  $data
     * @return array{total: float, paid: float, status: string, debt: float}
     */
    protected function resolveAmounts(float $subtotals, array $data): array
    {
        $total = round(max(0, $subtotals - (float) $data['sale_discount']), 1);
        $paid = round((float) $data['cash'] + (float) $data['qr'], 1);
        $status = $this->resolveStatus($paid, $total);

        return [
            'total' => $total,
            'paid' => $paid,
            'status' => $status,
            'debt' => $status === 'fiada' ? round(max(0, $total - $paid), 1) : 0.0,
        ];
    }

    /**
     * Exige nombre y teléfono del cliente cuando la venta queda "fiada".
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function ensureCustomerForFiada(string $status, array $data): void
    {
        if ($status !== 'fiada') {
            return;
        }

        $errors = [];

        if (blank($data['customer_name'] ?? null)) {
            $errors['customer_name'] = 'El nombre del cliente es obligatorio cuando la venta queda fiada.';
        }

        if (blank($data['customer_phone'] ?? null)) {
            $errors['customer_phone'] = 'El teléfono del cliente es obligatorio cuando la venta queda fiada.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Mensaje de confirmación indicando el estado y, si aplica, el monto fiao.
     */
    protected function saleStatusMessage(string $verb, string $status, float $debt): string
    {
        if ($status === 'fiada') {
            return "Venta {$verb} como FIADA. Fio: ".number_format($debt, 1).'.';
        }

        return "Venta {$verb} correctamente.";
    }

    /**
     * Determina el estado de la venta según el monto cubierto.
     *
     * "pagada" cuando efectivo + QR es mayor o igual al total (el "total" ya
     * tiene descontado el "sale_discount"); "fiada" en caso contrario. La
     * tolerancia de 0.05 evita falsos negativos por redondeo de punto flotante
     * (los importes se manejan con un decimal).
     */
    protected function resolveStatus(float $paid, float $total): string
    {
        return $paid >= $total - 0.05 ? 'pagada' : 'fiada';
    }

    /**
     * Atributos de un detalle de venta derivados de la presentación vendida.
     *
     * @return array<string, mixed>
     */
    protected function saleDetailAttributes(ProductPresentation $presentation, int $quantity): array
    {
        $conversionFactor = (int) $presentation->conversion_factor;
        $unitPrice = (float) $presentation->sale_price;

        return [
            'presentation_id' => $presentation->id,
            'quantity' => $quantity,
            'conversion_factor' => $conversionFactor,
            'base_quantity' => $conversionFactor * $quantity,
            'unit_price' => round($unitPrice, 1),
            'base_unit_cost_at_sale' => round((float) ($presentation->product?->base_unit_cost ?? 0), 2),
            'subtotal' => round($quantity * $unitPrice, 1),
        ];
    }

    /**
     * Valida y resuelve las líneas del carrito.
     *
     * Solo se aceptan presentaciones con "sale_enable" activo.
     *
     * @return array<int, array{presentation: ProductPresentation, quantity: int}>
     */
    protected function validatedDetails(Request $request): array
    {
        $validated = $request->validate([
            'details' => ['required', 'array', 'min:1'],
            'details.*.presentation_id' => [
                'required',
                'integer',
                Rule::exists('product_presentations', 'id')->where('sale_enable', true),
            ],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $presentations = ProductPresentation::with('product')
            ->whereIn('id', collect($validated['details'])->pluck('presentation_id'))
            ->get()
            ->keyBy('id');

        $details = [];

        foreach ($validated['details'] as $row) {
            $presentation = $presentations->get($row['presentation_id']);

            if ($presentation === null) {
                continue;
            }

            $details[] = [
                'presentation' => $presentation,
                'quantity' => (int) $row['quantity'],
            ];
        }

        return $details;
    }
}
