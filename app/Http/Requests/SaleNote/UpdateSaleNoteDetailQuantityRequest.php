<?php

namespace App\Http\Requests\SaleNote;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleNoteDetailQuantityRequest extends FormRequest
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
     * Solo se valida la cantidad. Los importes de la línea (conversion_factor,
     * base_quantity, unit_price y subtotal) se derivan en el servidor; el
     * formulario únicamente envía la nueva cantidad. El límite superior evita
     * desbordar el "integer" de la base al recalcular base_quantity.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
        ];
    }

    /**
     * Datos listos para persistir: la nueva cantidad de la línea.
     *
     * @return array{quantity: int}
     */
    public function dataForPersistence(): array
    {
        return [
            'quantity' => (int) $this->validated('quantity'),
        ];
    }
}
