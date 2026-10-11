<?php

namespace App\Services;

use App\Models\ProductPresentation;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly ProductPresentationService $presentations,
    ) {}

    /**
     * Registra una compra con sus líneas.
     *
     * La cabecera, las líneas, el incremento de stock y la reaplicación del costo
     * de referencia se ejecutan en una única transacción: si algo falla, no se
     * registra ninguna parte de la compra.
     *
     * El total se calcula como la suma de los subtotales de las líneas (el
     * subtotal de cada línea es cantidad × precio de compra ingresado). El precio
     * de compra ingresado pasa a ser el nuevo costo de referencia de la
     * presentación, que a su vez recalcula el costo por unidad base y los precios
     * de venta.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): Purchase
    {
        $details = $this->resolveDetails($data['details'] ?? []);
        $total = round(array_sum(array_column($details, 'total')), 2);

        return DB::transaction(function () use ($data, $userId, $details, $total) {
            $purchase = Purchase::create([
                'supplier' => $data['supplier'],
                'purchased_at' => $data['purchased_at'],
                'voucher_number' => $data['voucher_number'],
                'total' => $total,
                'user_id' => $userId,
            ]);

            $requirements = [];

            foreach ($details as $detail) {
                $presentation = $detail['presentation'];

                $purchase->purchaseDetails()->create($detail['attributes']);

                $requirements[] = [
                    'product_id' => (int) $presentation->product_id,
                    'base_quantity' => (int) $detail['attributes']['base_quantity'],
                ];

                $this->presentations->applyPurchaseCost($presentation, (float) $detail['attributes']['unit_price']);
            }

            $this->stock->incrementForPurchase($requirements);

            return $purchase;
        });
    }

    /**
     * Actualiza la cabecera de una compra y recalcula su total.
     *
     * Las líneas no se modifican (la compra es histórica): solo se actualizan los
     * datos del proveedor, la fecha y el comprobante, y el total se recalcula
     * desde los detalles existentes.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateHeader(Purchase $purchase, array $data): Purchase
    {
        $purchase->update([
            'supplier' => $data['supplier'],
            'purchased_at' => $data['purchased_at'],
            'voucher_number' => $data['voucher_number'],
            'total' => $this->totalFromDetails($purchase),
        ]);

        return $purchase;
    }

    /**
     * Total de la compra recalculado desde sus detalles (2 decimales).
     */
    public function totalFromDetails(Purchase $purchase): float
    {
        return round((float) $purchase->purchaseDetails()->sum('subtotal'), 2);
    }

    /**
     * Resuelve las líneas del formulario en presentaciones e importes.
     *
     * @param  array<int, array{presentation_id: int|string, quantity: int|string, unit_price: int|string|float}>  $rows
     * @return array<int, array{presentation: ProductPresentation, attributes: array<string, mixed>, total: float}>
     */
    protected function resolveDetails(array $rows): array
    {
        $presentations = ProductPresentation::with('product')
            ->whereIn('id', collect($rows)->pluck('presentation_id'))
            ->get()
            ->keyBy('id');

        $details = [];

        foreach ($rows as $row) {
            $presentation = $presentations->get($row['presentation_id']);

            if ($presentation === null) {
                continue;
            }

            $attributes = $this->purchaseDetailAttributes($presentation, (int) $row['quantity'], (float) $row['unit_price']);

            $details[] = [
                'presentation' => $presentation,
                'attributes' => $attributes,
                'total' => (float) $attributes['subtotal'],
            ];
        }

        return $details;
    }

    /**
     * Atributos de una línea a partir de la presentación y el precio de compra ingresado.
     *
     * El "unit_price" es el precio de compra indicado por el usuario y se guarda
     * como histórico; "conversion_factor" y "base_quantity" se derivan de la
     * presentación. El subtotal es cantidad × precio de compra.
     *
     * @return array<string, mixed>
     */
    protected function purchaseDetailAttributes(ProductPresentation $presentation, int $quantity, float $unitPrice): array
    {
        $conversionFactor = (int) $presentation->conversion_factor;
        $unitPrice = round($unitPrice, 2);

        return [
            'presentation_id' => $presentation->id,
            'quantity' => $quantity,
            'conversion_factor' => $conversionFactor,
            'base_quantity' => $conversionFactor * $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
        ];
    }
}
