<?php

namespace App\Http\Requests\ProductPresentation\Concerns;

trait HasPresentationRules
{
    /**
     * Reglas de validación comunes a la creación y edición de presentaciones.
     *
     * @return array<string, array<int, string>>
     */
    protected function presentationRules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversion_factor' => ['required', 'integer', 'min:0'],
            'presentation_size' => ['required', 'in:pequeño,grande'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
            'purchase_enable' => ['boolean'],
            'sale_enable' => ['boolean'],
            'reference_purchase_cost' => ['required_if:purchase_enable,1', 'nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'desired_profit' => ['required_if:sale_enable,1', 'nullable', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'],
            'barcode' => ['nullable', 'string', 'max:20'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'desired_profit.required_if' => 'La ganancia deseada es obligatoria cuando la venta está habilitada.',
        ];
    }

    /**
     * Datos listos para persistir, normalizando los importes según los toggles.
     *
     * @return array<string, mixed>
     */
    public function dataForPersistence(): array
    {
        $validated = $this->validated();

        $validated['reference_purchase_cost'] = $this->boolean('purchase_enable') && $this->filled('reference_purchase_cost')
            ? ($validated['reference_purchase_cost'] ?? null)
            : null;

        $validated['sale_price'] = $this->boolean('sale_enable') && $this->filled('sale_price')
            ? ($validated['sale_price'] ?? null)
            : null;

        // La ganancia solo aplica a presentaciones de venta; la columna no admite
        // null, así que una presentación solo de compra siempre guarda 0.
        $validated['desired_profit'] = $this->boolean('sale_enable')
            ? $validated['desired_profit']
            : 0;

        return $validated;
    }
}
