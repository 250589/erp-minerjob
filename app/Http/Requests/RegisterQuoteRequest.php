<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterQuoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'           => ['required', 'exists:suppliers,id'],
            'code'                  => ['nullable', 'string', 'max:30'],
            'currency'              => ['required', 'string', 'size:3'],
            'exchange_rate'         => ['required', 'numeric', 'min:0.0001'],
            'payment_term_days'     => ['required', 'integer', 'min:0'],
            'delivery_term_days'    => ['required', 'integer', 'min:0'],
            'validity_date'         => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string', 'max:500'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.requirement_item_id' => ['nullable', 'exists:requirement_items,id'],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required'          => 'Seleccione el proveedor.',
            'currency.required'             => 'La moneda es obligatoria.',
            'exchange_rate.required'        => 'El tipo de cambio es obligatorio.',
            'items.required'                => 'Ingrese al menos un ítem.',
            'items.*.description.required'  => 'La descripción del ítem es obligatoria.',
            'items.*.quantity.required'     => 'La cantidad es obligatoria.',
            'items.*.unit_price.required'   => 'El precio unitario es obligatorio.',
        ];
    }
}
