<?php

namespace App\Http\Requests\SaleNote;

use Illuminate\Foundation\Http\FormRequest;

class DeliverSaleNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza a "0" los importes de pago que llegan vacíos.
     *
     * Al entregar la nota se registra cuánto paga el cliente en efectivo y/o QR.
     * Los montos son obligatorios y no nulos en la venta generada; si el usuario
     * deja un input vacío se reemplaza por "0" antes de validar.
     */
    protected function prepareForValidation(): void
    {
        foreach (['received_cash', 'received_qr'] as $field) {
            if (blank($this->input($field))) {
                $this->merge([$field => '0']);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'decimal:0,1', 'max:999999999.9'];

        return [
            'received_cash' => $money,
            'received_qr' => $money,
        ];
    }

    /**
     * Datos listos para persistir: los montos recibidos al entregar.
     *
     * @return array<string, float>
     */
    public function dataForPersistence(): array
    {
        $validated = $this->validated();

        return [
            'received_cash' => (float) $validated['received_cash'],
            'received_qr' => (float) $validated['received_qr'],
        ];
    }
}
