<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Descuenta del stock de cada producto las cantidades vendidas en unidad base.
     *
     * El descuento se hace con un UPDATE atómico y condicional
     * ("stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad"). Si la
     * condición no se cumple (stock insuficiente), ninguna fila se modifica y se
     * lanza una excepción de validación que revierte la transacción en curso.
     *
     * En un entorno multiusuario, ordenar los bloqueos por "product_id" ascendente
     * evita interbloqueos entre transacciones concurrentes. PostgreSQL reevalúa la
     * condición tras liberar el bloqueo de la fila, por lo que dos ventas simultáneas
     * nunca pueden dejar el stock en negativo.
     *
     * @param  iterable<int, array{product_id: int, base_quantity: int}>  $requirements
     *
     * @throws ValidationException cuando un producto no tiene stock suficiente
     */
    public function decrementForSale(iterable $requirements): void
    {
        $quantities = [];

        foreach ($requirements as $requirement) {
            $productId = (int) $requirement['product_id'];
            $baseQuantity = (int) $requirement['base_quantity'];

            if ($baseQuantity <= 0) {
                continue;
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $baseQuantity;
        }

        // Bloqueo en orden ascendente de product_id para evitar interbloqueos.
        ksort($quantities);

        foreach ($quantities as $productId => $baseQuantity) {
            $affected = Product::query()
                ->whereKey($productId)
                ->where('stock', '>=', $baseQuantity)
                ->decrement('stock', $baseQuantity);

            if ($affected === 0) {
                $this->failInsufficientStock((int) $productId, $baseQuantity);
            }
        }
    }

    /**
     * Incrementa el stock de cada producto con las cantidades recibidas en unidad base.
     *
     * Se usa al registrar una compra: las cantidades por producto se agrupan y el
     * incremento se aplica en orden ascendente de "product_id", igual que en
     * decrementForSale(), para evitar interbloqueos entre transacciones concurrentes.
     *
     * @param  iterable<int, array{product_id: int, base_quantity: int}>  $requirements
     */
    public function incrementForPurchase(iterable $requirements): void
    {
        $quantities = [];

        foreach ($requirements as $requirement) {
            $productId = (int) $requirement['product_id'];
            $baseQuantity = (int) $requirement['base_quantity'];

            if ($baseQuantity <= 0) {
                continue;
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $baseQuantity;
        }

        ksort($quantities);

        foreach ($quantities as $productId => $baseQuantity) {
            Product::query()
                ->whereKey($productId)
                ->increment('stock', $baseQuantity);
        }
    }

    /**
     * Da de baja (active = false) los productos "de única vez" que quedaron sin
     * stock tras una venta directa o la entrega de una nota de venta.
     *
     * Solo afecta a productos con "single_batch" en true; el resto llega a 0 y
     * sigue activo para poder reponerse con compras. Debe llamarse dentro de la
     * misma transacción que el descuento de stock. El UPDATE es condicional
     * ("stock <= 0"), así que es seguro ante ventas concurrentes.
     *
     * No se usa en el ajuste manual de stock.
     *
     * @param  iterable<int, int>  $productIds
     * @return int cantidad de productos dados de baja
     */
    public function deactivateDepletedSingleBatch(iterable $productIds): int
    {
        $ids = collect($productIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('single_batch', true)
            ->where('active', true)
            ->where('stock', '<=', 0)
            ->update(['active' => false]);
    }

    /**
     * Aborta la operación indicando el producto y el stock disponible.
     *
     * @throws ValidationException
     */
    private function failInsufficientStock(int $productId, int $baseQuantity): never
    {
        $product = Product::find($productId);

        throw ValidationException::withMessages([
            'details' => sprintf(
                'Stock insuficiente para "%s": disponible %d, requerido %d.',
                $product?->name ?? "#{$productId}",
                (int) ($product?->stock ?? 0),
                $baseQuantity,
            ),
        ]);
    }
}
