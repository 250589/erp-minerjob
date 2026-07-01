<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'purchase_order_id'           => ['required', 'exists:purchase_orders,id'],
            'supplier_id'                 => ['required', 'exists:suppliers,id'],
            'series'                      => ['nullable', 'string', 'max:10'],
            'number'                      => ['required', 'string', 'max:20'],
            'issue_date'                  => ['required', 'date'],
            'currency'                    => ['required', 'string', 'size:3'],
            'exchange_rate'               => ['required', 'numeric', 'min:0.0001'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.description'         => ['required', 'string', 'max:255'],
            'items.*.quantity'            => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price'          => ['required', 'numeric', 'min:0'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required'                => 'El número de comprobante es obligatorio.',
            'issue_date.required'            => 'La fecha de emisión es obligatoria.',
            'items.required'                 => 'La factura debe tener al menos un ítem.',
            'items.*.description.required'   => 'La descripción de cada ítem es obligatoria.',
            'items.*.unit_price.required'    => 'El precio unitario de cada ítem es obligatorio.',
        ];
    }
}
