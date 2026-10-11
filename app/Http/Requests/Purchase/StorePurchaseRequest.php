<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\Purchase\Concerns\HasPurchaseRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    use HasPurchaseRules;

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
     * Solo se aceptan presentaciones habilitadas para compra. El resto de los
     * importes de cada línea (conversion_factor, base_quantity y subtotal) se
     * derivan de la presentación en el servidor; "unit_price" es el precio de
     * compra ingresado por el usuario.
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
                Rule::exists('product_presentations', 'id')->where('purchase_enable', true),
            ],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
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

        return [
            'supplier' => filled($validated['supplier'] ?? null) ? $validated['supplier'] : null,
            'purchased_at' => $validated['purchased_at'],
            'voucher_number' => filled($validated['voucher_number'] ?? null) ? $validated['voucher_number'] : null,
            'details' => $validated['details'],
        ];
    }
}
