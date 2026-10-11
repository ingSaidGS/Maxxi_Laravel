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
