<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Arma todos los reportes para un rango de fechas inclusivo.
     *
     * El filtro se aplica sobre "sales.sold_at" (fecha en que se registró la
     * venta). No se crean tablas: todo se calcula en vivo desde "sales" y
     * "sale_details".
     *
     * @return array{
     *     economic: array<string, float|int>,
     *     byDay: Collection<int, object>,
     *     byUser: Collection<int, object>,
     *     topProducts: Collection<int, object>
     * }
     */
    public function forRange(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        return [
            'economic' => $this->economic($start, $end),
            'byDay' => $this->byDay($start, $end),
            'byUser' => $this->byUser($start, $end),
            'topProducts' => $this->topProducts($start, $end),
        ];
    }

    /**
     * Resumen económico del rango: ventas, cobros, fiado, costo y ganancia.
     *
     * "net" es la suma de "total" (ya con el descuento aplicado); "gross" es la
     * suma de subtotales de las líneas, que equivale a "net" + "discount". La
     * inversión (costo de lo vendido) es la suma de
     * "base_quantity * costo" de cada línea, por lo que la ganancia bruta es
     * "net" - "cost".
     *
     * El costo de cada línea usa, en orden: el costo histórico guardado al
     * momento de la venta ("base_unit_cost_at_sale"), el costo actual de la
     * presentación ("reference_purchase_cost" dividido por su factor de
     * conversión) y, por último, el costo por unidad base del producto
     * ("products.base_unit_cost"). Así las ventas antiguas sin costo cargado
     * igual muestran una inversión estimada.
     *
     * @return array<string, float|int>
     */
    protected function economic(Carbon $start, Carbon $end): array
    {
        $totals = Sale::query()
            ->whereBetween('sold_at', [$start, $end])
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(total), 0) as net')
            ->selectRaw('COALESCE(SUM(sale_discount), 0) as discount')
            ->selectRaw('COALESCE(SUM(cash), 0) as cash')
            ->selectRaw('COALESCE(SUM(qr), 0) as qr')
            ->selectRaw('COALESCE(SUM(debt), 0) as debt')
            ->first();

        $cost = (float) SaleDetail::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_details.presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw(
                'COALESCE(SUM(COALESCE('
                .'NULLIF(sale_details.base_unit_cost_at_sale, 0), '
                .'NULLIF(product_presentations.reference_purchase_cost, 0) / NULLIF(product_presentations.conversion_factor, 0), '
                .'NULLIF(products.base_unit_cost, 0)'
                .') * sale_details.base_quantity), 0) as cost'
            )
            ->value('cost');

        $count = (int) $totals->sales_count;
        $net = (float) $totals->net;
        $discount = (float) $totals->discount;
        $profit = $net - $cost;

        return [
            'sales_count' => $count,
            'gross' => $net + $discount,
            'discount' => $discount,
            'net' => $net,
            'cash' => (float) $totals->cash,
            'qr' => (float) $totals->qr,
            'debt' => (float) $totals->debt,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $net > 0 ? ($profit / $net) * 100 : 0.0,
            'cost_share' => $net > 0 ? ($cost / $net) * 100 : 0.0,
            'avg_ticket' => $count > 0 ? $net / $count : 0.0,
        ];
    }

    /**
     * Ventas agrupadas por día (ascendente).
     *
     * @return Collection<int, object>
     */
    protected function byDay(Carbon $start, Carbon $end): Collection
    {
        return Sale::query()
            ->whereBetween('sold_at', [$start, $end])
            ->selectRaw('DATE(sold_at) as day')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(total), 0) as net')
            ->selectRaw('COALESCE(SUM(cash), 0) as cash')
            ->selectRaw('COALESCE(SUM(qr), 0) as qr')
            ->selectRaw('COALESCE(SUM(debt), 0) as debt')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Cobros y ventas agrupados por usuario del sistema.
     *
     * @return Collection<int, object>
     */
    protected function byUser(Carbon $start, Carbon $end): Collection
    {
        return Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('users.id as user_id')
            ->selectRaw('users.name as user_name')
            ->selectRaw('COUNT(sales.id) as sales_count')
            ->selectRaw('COALESCE(SUM(sales.total), 0) as net')
            ->selectRaw('COALESCE(SUM(sales.cash), 0) as cash')
            ->selectRaw('COALESCE(SUM(sales.qr), 0) as qr')
            ->selectRaw('COALESCE(SUM(sales.debt), 0) as debt')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('net')
            ->get();
    }

    /**
     * Productos más vendidos del rango, ordenados por cantidad (unidad base).
     *
     * Se agrupa por producto (sumando todas sus presentaciones). "quantity" es la
     * suma de "base_quantity" (unidad base del producto); "revenue" la suma de
     * subtotales.
     *
     * @return Collection<int, object>
     */
    protected function topProducts(Carbon $start, Carbon $end): Collection
    {
        return SaleDetail::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_details.presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.base_unit_id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('products.id as product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('units.symbol as unit_symbol')
            ->selectRaw('COALESCE(SUM(sale_details.base_quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(sale_details.subtotal), 0) as revenue')
            ->groupBy('products.id', 'products.name', 'units.symbol')
            ->orderByDesc('quantity')
            ->get();
    }
}
