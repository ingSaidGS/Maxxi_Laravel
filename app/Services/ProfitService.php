<?php

namespace App\Services;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleNote;

/**
 * Costo de lo vendido y ganancia bruta de ventas y notas de venta.
 *
 * Mismo criterio que ReportService::economic(): ganancia bruta = total (ya con
 * el descuento aplicado) - costo. El costo de cada línea es "cantidad base ×
 * costo por unidad base", y el costo por unidad base usa, en orden, el primero
 * que no sea 0:
 *
 *  1. el costo de la línea (el guardado al vender o, en una nota pendiente, el
 *     costo actual del producto, que es el que se usará al entregarla);
 *  2. el costo de referencia de la presentación / su factor de conversión;
 *  3. el costo por unidad base del producto.
 *
 * Si alguna línea necesitó los respaldos 2 o 3, el resultado se marca como
 * "estimated". Requiere cargadas las relaciones "presentation.product" de las
 * líneas (si no, se cargan de forma diferida).
 */
class ProfitService
{
    /**
     * Ganancia bruta de una venta registrada.
     *
     * @return array{cost: float, profit: float, estimated: bool}
     */
    public function forSale(Sale $sale): array
    {
        $estimated = false;
        $cost = 0.0;

        foreach ($sale->saleDetails as $detail) {
            $cost += $this->lineCost(
                (float) $detail->base_unit_cost_at_sale,
                $detail->presentation,
                (int) $detail->base_quantity,
                $estimated,
            );
        }

        return $this->result((float) $sale->total, $cost, $estimated);
    }

    /**
     * Ganancia bruta de una nota de venta según su estado, o null si no aplica.
     *
     * - pendiente: estimada con el costo actual de sus líneas activas (siempre
     *   "estimated": el costo real se fija al entregar);
     * - entregada: la ganancia de la venta que generó;
     * - anulada (o entregada sin venta): null.
     *
     * @return array{cost: float, profit: float, estimated: bool}|null
     */
    public function forSaleNote(SaleNote $saleNote): ?array
    {
        if ($saleNote->status === 'entregada') {
            return $saleNote->sale !== null ? $this->forSale($saleNote->sale) : null;
        }

        if ($saleNote->status !== 'pendiente') {
            return null;
        }

        $estimated = true;
        $cost = 0.0;

        foreach ($saleNote->saleNoteDetails->where('active', true) as $detail) {
            $cost += $this->lineCost(
                (float) ($detail->presentation?->product?->base_unit_cost ?? 0),
                $detail->presentation,
                (int) $detail->base_quantity,
                $estimated,
            );
        }

        return $this->result((float) $saleNote->total, $cost, $estimated);
    }

    /**
     * Costo de una línea con el orden de respaldo descrito en la clase.
     */
    protected function lineCost(float $unitCost, ?ProductPresentation $presentation, int $baseQuantity, bool &$estimated): float
    {
        if ($unitCost <= 0) {
            $factor = (int) ($presentation?->conversion_factor ?? 0);
            $referenceCost = (float) ($presentation?->reference_purchase_cost ?? 0);

            $unitCost = $factor > 0 && $referenceCost > 0
                ? $referenceCost / $factor
                : (float) ($presentation?->product?->base_unit_cost ?? 0);

            $estimated = $estimated || $unitCost > 0;
        }

        return $unitCost * $baseQuantity;
    }

    /**
     * @return array{cost: float, profit: float, estimated: bool}
     */
    protected function result(float $total, float $cost, bool $estimated): array
    {
        return [
            'cost' => $cost,
            'profit' => round($total - $cost, 1),
            'estimated' => $estimated,
        ];
    }
}
