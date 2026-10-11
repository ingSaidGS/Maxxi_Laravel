<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
use Illuminate\Validation\ValidationException;

class ProductPresentationService
{
    /**
     * Crea una presentación de producto aplicando las reglas de negocio.
     *
     * Si la presentación resultante queda habilitada para compra y activa, se
     * garantiza que sea la única en esas condiciones y se recalculan los precios
     * de venta de las presentaciones activas de venta del producto.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductPresentation
    {
        $purchaseEnabled = filter_var($data['purchase_enable'] ?? false, FILTER_VALIDATE_BOOL);
        $isActive = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOL);

        if ($purchaseEnabled && $isActive) {
            $this->ensureSinglePurchasePresentation((int) $data['product_id']);
        }

        $presentation = ProductPresentation::create($data);

        if ($purchaseEnabled && $isActive) {
            $this->syncBaseUnitCost($presentation);
            $this->recalculateSalePrices($presentation);
        }

        return $presentation;
    }

    /**
     * Actualiza una presentación aplicando las reglas de negocio.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductPresentation $presentation, array $data): ProductPresentation
    {
        $purchaseEnabled = filter_var($data['purchase_enable'] ?? false, FILTER_VALIDATE_BOOL);
        $isActive = (bool) $presentation->active;

        if ($purchaseEnabled && $isActive) {
            $this->ensureSinglePurchasePresentation((int) $data['product_id'], $presentation->id);
        }

        $presentation->update($data);

        if ($purchaseEnabled && $isActive) {
            $this->syncBaseUnitCost($presentation);
            $this->recalculateSalePrices($presentation);
        }

        return $presentation;
    }

    /**
     * Reactiva (alta lógica) una presentación.
     *
     * @throws ValidationException
     */
    public function restore(ProductPresentation $presentation): ProductPresentation
    {
        if ($presentation->purchase_enable) {
            $this->ensureSinglePurchasePresentation($presentation->product_id, $presentation->id);
        }

        $presentation->update(['active' => true]);

        return $presentation;
    }

    /**
     * Costo por unidad mínima según la presentación de compra activa del producto.
     *
     * Devuelve null si el producto no tiene una presentación de compra activa
     * con factor de conversión mayor que cero.
     */
    public function purchaseCostPerBaseUnit(Product $product): ?float
    {
        $purchasePresentation = ProductPresentation::query()
            ->where('product_id', $product->id)
            ->where('purchase_enable', true)
            ->where('active', true)
            ->first();

        $conversionFactor = (int) ($purchasePresentation?->conversion_factor ?? 0);

        if ($purchasePresentation === null || $conversionFactor <= 0) {
            return null;
        }

        return (float) $purchasePresentation->reference_purchase_cost / $conversionFactor;
    }

    /**
     * Evita que exista más de una presentación activa y habilitada para compra por producto.
     *
     * Regla de negocio: por cada producto solo puede existir una presentación con
     * "purchase_enable" y "active" en true a la vez.
     *
     * @throws ValidationException
     */
    private function ensureSinglePurchasePresentation(int $productId, ?int $ignoreId = null): void
    {
        $conflict = ProductPresentation::query()
            ->where('product_id', $productId)
            ->where('purchase_enable', true)
            ->where('active', true)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'purchase_enable' => 'Ya existe una presentación activa habilitada para compra en este producto.',
            ]);
        }
    }

    /**
     * Sincroniza el costo por unidad base del producto con la presentación de compra.
     *
     * "base_unit_cost" = reference_purchase_cost / conversion_factor.
     * Se omite si el factor de conversión es menor o igual a cero.
     */
    private function syncBaseUnitCost(ProductPresentation $purchasePresentation): void
    {
        $conversionFactor = (int) $purchasePresentation->conversion_factor;

        if ($conversionFactor <= 0) {
            return;
        }

        $baseUnitCost = (float) $purchasePresentation->reference_purchase_cost / $conversionFactor;

        $purchasePresentation->product?->update(['base_unit_cost' => round($baseUnitCost, 2)]);
    }

    /**
     * Recalcula el precio de venta de las presentaciones activas habilitadas para venta.
     *
     * A partir del costo por unidad mínima de la presentación de compra, cada
     * presentación recibe: costo_unitario * conversion_factor + desired_profit,
     * redondeado a un decimal.
     */
    private function recalculateSalePrices(ProductPresentation $purchasePresentation): void
    {
        $conversionFactor = (int) $purchasePresentation->conversion_factor;

        if ($conversionFactor <= 0) {
            return;
        }

        $costPerBaseUnit = (float) $purchasePresentation->reference_purchase_cost / $conversionFactor;

        ProductPresentation::query()
            ->where('product_id', $purchasePresentation->product_id)
            ->where('active', true)
            ->where('sale_enable', true)
            ->get()
            ->each(function (ProductPresentation $presentation) use ($costPerBaseUnit) {
                $salePrice = $costPerBaseUnit * (int) $presentation->conversion_factor + (float) $presentation->desired_profit;

                $presentation->update(['sale_price' => round($salePrice, 1)]);
            });
    }
}
