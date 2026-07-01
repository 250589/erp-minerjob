<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'origin_warehouse_id'           => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id'      => [
                'required',
                'exists:warehouses,id',
                'different:origin_warehouse_id',
            ],
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.product_id'            => ['required', 'exists:products,id'],
            'items.*.quantity_requested'    => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_warehouse_id.required'      => 'Seleccione el almacén de origen.',
            'destination_warehouse_id.required' => 'Seleccione el almacén de destino.',
            'destination_warehouse_id.different' => 'El almacén destino debe ser diferente al origen.',
            'items.required'                    => 'Agregue al menos un producto a trasladar.',
            'items.*.product_id.required'       => 'Seleccione el producto.',
            'items.*.quantity_requested.required' => 'Ingrese la cantidad a trasladar.',
            'items.*.quantity_requested.min'    => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
