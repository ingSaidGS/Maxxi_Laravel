<?php

namespace App\Http\Controllers;

use App\Models\ProductPresentation;
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Los importes del detalle se derivan de la presentación vendida.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $presentation = ProductPresentation::with('product')->findOrFail($validated['presentation_id']);

        $saleDetail = SaleDetail::create([
            'sale_id' => $validated['sale_id'],
            ...$this->detailAttributes($presentation, (int) $validated['quantity']),
        ]);

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

        $presentation = ProductPresentation::with('product')->findOrFail($validated['presentation_id']);

        $saleDetail->update([
            'sale_id' => $validated['sale_id'],
            ...$this->detailAttributes($presentation, (int) $validated['quantity']),
        ]);

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
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Atributos del detalle derivados de la presentación vendida.
     *
     * @return array<string, mixed>
     */
    protected function detailAttributes(ProductPresentation $presentation, int $quantity): array
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
}
