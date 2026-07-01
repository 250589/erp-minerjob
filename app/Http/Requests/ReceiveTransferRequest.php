<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'                             => ['required', 'in:aceptado,observado'],
            'observations'                       => ['nullable', 'string', 'max:500'],
            'items'                              => ['required', 'array', 'min:1'],
            'items.*.transfer_order_item_id'     => ['required', 'exists:transfer_order_items,id'],
            'items.*.quantity_received'          => ['required', 'numeric', 'min:0'],
            'items.*.condition_status'           => ['required', 'in:bueno,danado'],
            'items.*.notes'                      => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'                        => 'Indique el estado de la recepción.',
            'items.*.quantity_received.required'     => 'Ingrese la cantidad recibida.',
            'items.*.condition_status.required'      => 'Indique el estado de cada ítem.',
        ];
    }
}
