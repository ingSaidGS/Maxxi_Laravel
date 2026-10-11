<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\Purchase\Concerns\HasPurchaseRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
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
     * La edición solo afecta la cabecera; las líneas de la compra no se modifican.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return $this->headerRules();
    }

    /**
     * Datos listos para persistir (solo la cabecera).
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
        ];
    }
}
