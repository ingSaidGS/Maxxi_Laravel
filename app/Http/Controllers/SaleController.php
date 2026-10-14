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
    public function index(Request $request): View
    {
        $today = now()->startOfDay();

        $perPage = (int) $request->input('per_page', 15);

        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $sales = Sale::with('user')
            ->where('sold_at', '>=', $today)
            ->where('sold_at', '<', $today->copy()->addDay())
            ->orderByDesc('sold_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('sale.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Se cargan las presentaciones con "sale_enable" activo para armar el carrito.
     */
    public function create(): View
    {
        $presentations = ProductPresentation::with(['product.baseUnit', 'unit'])
            ->where('sale_enable', true)
            ->where('active', true)
            ->whereHas('product', function ($query) {
                $query->where('active', true);
            })
            ->orderBy('id')
            ->get()
            ->map(function (ProductPresentation $presentation): array {
                // La presentación se identifica por su unidad + factor de conversión
                // (cuántas unidades base contiene). El "sizeLabel" es la equivalencia
                // legible, p. ej. "x24 kg", que evita confundir dos presentaciones
                // del mismo producto con la misma unidad y distinto factor.
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
        $this->normalizeEmptyMoney($request);

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
        $validated['cash'] = $amounts['cash'];
        $validated['qr'] = $amounts['qr'];
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
            ->route('sales.create')
            ->with('success', [
                'status' => $amounts['status'],
                'debt' => $amounts['debt'],
                'total' => $amounts['total'],
                'change' => $amounts['change'],
                'sale_id' => $sale->id,
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): View
    {
        $sale->load(['user', 'saleDetails.presentation.product.baseUnit', 'saleDetails.presentation.product.presentations', 'saleDetails.presentation.unit']);

        return view('sale.show', compact('sale'));
    }

    /**
     * Muestra el ticket de la venta listo para impresión térmica.
     *
     * La vista es autónoma (no usa el layout del sistema) para poder imprimir en
     * rollos de 80mm o 58mm. Carga las mismas relaciones que show() para poder
     * expresar la cantidad de cada línea en la unidad de su presentación.
     */
    public function ticket(Sale $sale): View
    {
        $sale->load(['user', 'saleDetails.presentation.product.baseUnit', 'saleDetails.presentation.product.presentations', 'saleDetails.presentation.unit']);

        return view('sale.ticket', compact('sale'));
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
        $this->normalizeEmptyMoney($request);

        $validated = $request->validate($this->rules());

        $subtotals = (float) $sale->saleDetails()->sum('subtotal');
        $amounts = $this->resolveAmounts($subtotals, $validated);
        $this->ensureCustomerForFiada($amounts['status'], $validated);

        $validated['total'] = $amounts['total'];
        $validated['cash'] = $amounts['cash'];
        $validated['qr'] = $amounts['qr'];
        $validated['status'] = $amounts['status'];
        $validated['debt'] = $amounts['debt'];

        $sale->update($validated);

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', $this->saleStatusMessage('actualizada', $amounts['status'], $amounts['debt']));
    }

    /**
     * Registra el cobro (total o parcial) de una venta fiada.
     *
     * Los montos recibidos se suman a lo ya pagado en la venta y el estado y el
     * fio se recalculan sobre el total existente: si el cobro cubre la deuda la
     * venta queda "pagada"; si es parcial sigue "fiada" con el fio restante. No
     * modifica los detalles, el total ni el descuento de la venta.
     */
    public function cobrar(Request $request, Sale $sale): RedirectResponse
    {
        if ($sale->status !== 'fiada') {
            return redirect()->route('sales.index')
                ->withErrors(['cobro' => 'Esta venta ya está pagada.']);
        }

        $this->normalizeEmptyMoney($request, ['received_cash', 'received_qr']);

        $validated = $request->validate([
            'received_cash' => ['numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
            'received_qr' => ['numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
        ]);

        $receivedCash = (float) ($validated['received_cash'] ?? 0);
        $receivedQr = (float) ($validated['received_qr'] ?? 0);

        if (round($receivedCash + $receivedQr, 1) <= 0) {
            throw ValidationException::withMessages([
                'cobro' => 'Ingresa un monto en efectivo o QR para registrar el cobro.',
            ]);
        }

        $amounts = $this->resolveAmounts((float) $sale->total, [
            'sale_discount' => 0,
            'cash' => (float) $sale->cash + $receivedCash,
            'qr' => (float) $sale->qr + $receivedQr,
        ]);

        $sale->update([
            'cash' => $amounts['cash'],
            'qr' => $amounts['qr'],
            'status' => $amounts['status'],
            'debt' => $amounts['debt'],
        ]);

        $message = $amounts['status'] === 'pagada'
            ? "Cobro registrado. La venta #{$sale->id} quedó PAGADA."
            : 'Cobro parcial registrado. Fio pendiente: Bs. '.number_format($amounts['debt'], 1).'.';

        if ($amounts['change'] > 0) {
            $message .= ' Vuelto: Bs. '.number_format($amounts['change'], 1).'.';
        }

        return redirect()->route('sales.index')->with('status', $message);
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
     * Normaliza a "0" los importes de pago que llegan vacíos.
     *
     * "sale_discount", "cash" y "qr" son obligatorios y no nulos en la base; si el
     * usuario deja el input vacío, el navegador envía una cadena vacía. Aquí se
     * reemplaza ese vacío (o solo espacios) por "0" antes de validar.
     *
     * @param  array<int, string>  $fields
     */
    protected function normalizeEmptyMoney(Request $request, array $fields = ['sale_discount', 'cash', 'qr']): void
    {
        $defaults = [];

        foreach ($fields as $field) {
            $value = $request->input($field);

            if ($value === null || trim((string) $value) === '') {
                $defaults[$field] = '0';
            }
        }

        if ($defaults !== []) {
            $request->merge($defaults);
        }
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
     * El vuelto se devuelve del efectivo: si el pago supera el total, se registra
     * en "cash" solo lo justo para que efectivo + QR == total (el excedente es el
     * vuelto y no se guarda). Si el QR por sí solo superara el total, se ajusta
     * también el QR.
     *
     * @param  array<string, mixed>  $data
     * @return array{total: float, paid: float, cash: float, qr: float, change: float, status: string, debt: float}
     */
    protected function resolveAmounts(float $subtotals, array $data): array
    {
        $total = round(max(0, $subtotals - (float) $data['sale_discount']), 1);
        $cash = round((float) $data['cash'], 1);
        $qr = round((float) $data['qr'], 1);
        $paid = round($cash + $qr, 1);
        $status = $this->resolveStatus($paid, $total);
        $debt = $status === 'fiada' ? round(max(0, $total - $paid), 1) : 0.0;
        $change = $status === 'fiada' ? 0.0 : round(max(0, $paid - $total), 1);

        if ($change > 0) {
            $qr = min($qr, $total);
            $cash = round(max(0, $total - $qr), 1);
        }

        return [
            'total' => $total,
            'paid' => $paid,
            'cash' => $cash,
            'qr' => $qr,
            'change' => $change,
            'status' => $status,
            'debt' => $debt,
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
