<?php

namespace App\Http\Controllers;

use App\Models\SaleDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $details = SaleDetail::orderBy('id')->get();

        return response()->json($details);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Sin vistas todavía: se devuelven los valores por defecto del formulario.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'sale_id' => null,
            'presentation_id' => null,
            'quantity' => 1,
            'conversion_factor' => 1,
            'sale_enable' => true,
            'subtotal' => 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $saleDetail = SaleDetail::create($validated);

        return response()->json($saleDetail, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SaleDetail $saleDetail): JsonResponse
    {
        return response()->json($saleDetail);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Sin vistas todavía: se devuelven los datos actuales del detalle.
     */
    public function edit(SaleDetail $saleDetail): JsonResponse
    {
        return response()->json($saleDetail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SaleDetail $saleDetail): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $saleDetail->update($validated);

        return response()->json($saleDetail);
    }

    /**
     * Los detalles de venta no se eliminan.
     *
     * La tabla "sale_details" no tiene columna "active" y los detalles pertenecen
     * a una venta que tampoco se elimina, por lo que se rechaza la operación.
     */
    public function destroy(SaleDetail $saleDetail): JsonResponse
    {
        return response()->json([
            'message' => 'Los detalles de venta no se pueden eliminar.',
        ], 405);
    }

    /**
     * Reglas de validación según las columnas de "sale_details".
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'presentation_id' => ['required', 'integer', 'exists:product_presentations,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'conversion_factor' => ['required', 'integer', 'min:0'],
            'sale_enable' => ['boolean'],
            'subtotal' => ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
        ];
    }
}
