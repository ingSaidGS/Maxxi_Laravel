<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $sales = Sale::orderByDesc('sold_at')->get();

        return response()->json($sales);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Sin vistas todavía: se devuelven los valores por defecto del formulario.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'customer_name' => '',
            'customer_phone' => '',
            'sold_at' => null,
            'total' => 0,
            'status' => 'pagada',
            'user_id' => null,
            'sale_discount' => 0,
            'cash' => 0,
            'qr' => 0,
            'debt' => 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $sale = Sale::create($validated);

        return response()->json($sale, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): JsonResponse
    {
        return response()->json($sale);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Sin vistas todavía: se devuelven los datos actuales de la venta.
     */
    public function edit(Sale $sale): JsonResponse
    {
        return response()->json($sale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $sale->update($validated);

        return response()->json($sale);
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
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'];

        return [
            'customer_name' => ['required', 'string', 'max:20'],
            'customer_phone' => ['required', 'string', 'max:10'],
            'sold_at' => ['required', 'date'],
            'total' => $money,
            'status' => ['required', 'in:pagada,fiada'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'sale_discount' => $money,
            'cash' => $money,
            'qr' => $money,
            'debt' => $money,
        ];
    }
}
