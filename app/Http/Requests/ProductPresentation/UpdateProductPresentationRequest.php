<?php

namespace App\Http\Requests\ProductPresentation;

use App\Http\Requests\ProductPresentation\Concerns\HasPresentationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPresentationRequest extends FormRequest
{
    use HasPresentationRules;

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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ] + $this->presentationRules();
    }
}
