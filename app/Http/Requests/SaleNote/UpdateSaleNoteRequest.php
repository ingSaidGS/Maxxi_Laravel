<?php

namespace App\Http\Requests\SaleNote;

use App\Http\Requests\SaleNote\Concerns\HasSaleNoteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleNoteRequest extends FormRequest
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
     * La edición solo afecta la cabecera; las líneas se quitan o reactivan de
     * forma individual y el total se recalcula en el servidor desde las líneas
     * activas.
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
        return $this->headerAttributes($this->validated());
    }
}
