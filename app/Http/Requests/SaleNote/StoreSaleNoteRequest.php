<?php

namespace App\Http\Requests\SaleNote;

use App\Http\Requests\SaleNote\Concerns\HasSaleNoteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleNoteRequest extends FormRequest
{
    use HasSaleNoteRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza los importes y campos opcionales vacíos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeHeaderInput();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Solo se aceptan presentaciones habilitadas para venta. Los importes de cada
     * línea (conversion_factor, base_quantity, unit_price, base_unit_cost_at_note
     * y subtotal) se derivan de la presentación en el servidor; el formulario
     * únicamente envía la presentación y la cantidad.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->headerRules() + [
            'details' => ['required', 'array', 'min:1'],
            'details.*.presentation_id' => [
                'required',
                'integer',
                Rule::exists('product_presentations', 'id')->where('sale_enable', true),
            ],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Datos listos para persistir: la cabecera y las líneas del formulario.
     *
     * @return array<string, mixed>
     */
    public function dataForPersistence(): array
    {
        $validated = $this->validated();

        return $this->headerAttributes($validated) + [
            'details' => $validated['details'],
        ];
    }
}
