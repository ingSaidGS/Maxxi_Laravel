<?php

namespace App\Http\Requests\SaleNote\Concerns;

trait HasSaleNoteRules
{
    /**
     * Reglas de validación de la cabecera de la nota de venta.
     *
     * Los límites siguen las columnas de "sale_notes": customer_name varchar(20),
     * customer_phone varchar(10), delivery_latitude/longitude decimal(10,7) y
     * sale_discount decimal(10,1). El nombre y el teléfono del cliente son
     * opcionales; las coordenadas y "scheduled_for" también (la nota puede
     * registrarse sin ubicación ni fecha programada). "total", "status",
     * "delivered_at", "sale_id" y "user_id" no se validan: se derivan en el
     * servidor.
     *
     * @return array<string, array<int, string>>
     */
    protected function headerRules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:20'],
            'customer_phone' => ['nullable', 'string', 'max:10'],
            'noted_at' => ['required', 'date'],
            'scheduled_for' => ['nullable', 'date'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sale_discount' => ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
        ];
    }

    /**
     * Normaliza los campos de la cabecera antes de validar.
     *
     * "sale_discount" es obligatorio y no nulo en la base; si el usuario deja el
     * input vacío se reemplaza por "0". Los campos opcionales que llegan vacíos
     * (solo espacios) se convierten a null para guardarse como NULL.
     */
    protected function normalizeHeaderInput(): void
    {
        if (blank($this->input('sale_discount'))) {
            $this->merge(['sale_discount' => '0']);
        }

        foreach (['customer_name', 'customer_phone', 'scheduled_for', 'delivery_latitude', 'delivery_longitude'] as $field) {
            if ($this->has($field) && blank($this->input($field))) {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Atributos de la cabecera listos para persistir.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $validated): array
    {
        return [
            'customer_name' => filled($validated['customer_name'] ?? null) ? $validated['customer_name'] : null,
            'customer_phone' => filled($validated['customer_phone'] ?? null) ? $validated['customer_phone'] : null,
            'noted_at' => $validated['noted_at'],
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'delivery_latitude' => $validated['delivery_latitude'] ?? null,
            'delivery_longitude' => $validated['delivery_longitude'] ?? null,
            'sale_discount' => $validated['sale_discount'],
        ];
    }
}
