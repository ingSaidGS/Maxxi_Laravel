<?php

namespace App\Http\Controllers;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
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
        $users = User::orderBy('name')->get();

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

        return view('sale.create', compact('users', 'presentations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * El total se calcula como (suma de subtotales - descuento).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $details = $this->validatedDetails($request);

        $sale = DB::transaction(function () use ($validated, $details) {
            $subtotals = 0.0;

            foreach ($details as $detail) {
                $subtotals += $detail['quantity'] * (float) $detail['presentation']->sale_price;
            }

            $validated['total'] = round(max(0, $subtotals - (float) $validated['sale_discount']), 1);

            $sale = Sale::create($validated);

            foreach ($details as $detail) {
                $presentation = $detail['presentation'];

                $sale->saleDetails()->create([
                    'presentation_id' => $presentation->id,
                    'quantity' => $detail['quantity'],
                    'conversion_factor' => $presentation->conversion_factor,
                    'sale_enable' => $presentation->sale_enable,
                    'subtotal' => round($detail['quantity'] * (float) $presentation->sale_price, 1),
                ]);
            }

            return $sale;
        });

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Venta registrada correctamente.');
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
        $users = User::orderBy('name')->get();

        return view('sale.edit', compact('sale', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * El total se recalcula a partir de los detalles existentes.
     */
    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $subtotals = (float) $sale->saleDetails()->sum('subtotal');
        $validated['total'] = round(max(0, $subtotals - (float) $validated['sale_discount']), 1);

        $sale->update($validated);

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Venta actualizada correctamente.');
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
     * "total" no se valida aquí: se calcula en el servidor.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'];

        return [
            'customer_name' => ['required', 'string', 'max:20'],
            'customer_phone' => ['required', 'string', 'max:10'],
            'sold_at' => ['required', 'date'],
            'status' => ['required', 'in:pagada,fiada'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'sale_discount' => $money,
            'cash' => $money,
            'qr' => $money,
            'debt' => $money,
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

        $presentations = ProductPresentation::whereIn('id', collect($validated['details'])->pluck('presentation_id'))
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
