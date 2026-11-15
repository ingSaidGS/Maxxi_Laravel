<?php

namespace App\Http\Requests\SaleNote;

use Illuminate\Foundation\Http\FormRequest;

class RefreshSaleNotePricesRequest extends FormRequest
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
     * "prices" son los precios vigentes que el encargado vio en el aviso, por
     * línea ([id de línea => precio]). No se usan como precio de la nota: el
     * servidor aplica siempre el precio del catálogo y solo los compara para
     * detectar si volvieron a cambiar (ver SaleNoteService::refreshPrices()).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1'],
            'prices.*' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Datos listos para el servicio: [id de línea => precio mostrado].
     *
     * @return array<int, float>
     */
    public function dataForPersistence(): array
    {
        return collect($this->validated('prices'))
            ->mapWithKeys(fn ($price, $id) => [(int) $id => (float) $price])
            ->all();
    }
}
