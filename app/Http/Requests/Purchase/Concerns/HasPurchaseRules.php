<?php

namespace App\Http\Requests\Purchase\Concerns;

trait HasPurchaseRules
{
    /**
     * Reglas de validación de la cabecera de la compra.
     *
     * Los límites siguen las columnas de "purchases": supplier varchar(15) y
     * voucher_number varchar(8). "supplier" y "voucher_number" son opcionales;
     * "total" y "user_id" no se validan (el total se calcula en el servidor y el
     * usuario se toma de la sesión autenticada).
     *
     * @return array<string, array<int, string>>
     */
    protected function headerRules(): array
    {
        return [
            'supplier' => ['nullable', 'string', 'max:15'],
            'purchased_at' => ['required', 'date'],
            'voucher_number' => ['nullable', 'string', 'max:8'],
        ];
    }
}
