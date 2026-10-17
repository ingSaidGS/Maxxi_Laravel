<?php

namespace App\Http\Requests\SaleNote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleNoteDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Solo se aceptan presentaciones habilitadas para venta (mismo criterio que
     * StoreSaleNoteRequest). Los importes de la línea (conversion_factor,
     * base_quantity, unit_price, base_unit_cost_at_note y subtotal) se derivan
     * en el servidor a partir de la presentación; el formulario únicamente envía
     * la presentación y la cantidad. El límite superior evita desbordar el
     * "integer" de la base al calcular base_quantity.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'presentation_id' => [
                'required',
                'integer',
                Rule::exists('product_presentations', 'id')->where('sale_enable', true),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
        ];
    }

    /**
     * Datos listos para persistir: la presentación y la cantidad.
     *
     * @return array{presentation_id: int, quantity: int}
     */
    public function dataForPersistence(): array
    {
        return [
            'presentation_id' => (int) $this->validated('presentation_id'),
            'quantity' => (int) $this->validated('quantity'),
        ];
    }
}
