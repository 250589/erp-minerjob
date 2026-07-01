<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseReceptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'purchase_order_id'              => ['required', 'exists:purchase_orders,id'],
            'warehouse_id'                   => ['required', 'exists:warehouses,id'],
            'invoice_id'                     => ['nullable', 'exists:invoices,id'],
            'status'                         => ['required', 'in:completa,parcial,observada'],
            'observations'                   => ['nullable', 'string', 'max:500'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.product_id'             => ['required', 'exists:products,id'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.quantity_ordered'       => ['required', 'numeric', 'min:0'],
            'items.*.quantity_received'      => ['required', 'numeric', 'min:0'],
            'items.*.unit_purchase_price'    => ['required', 'numeric', 'min:0'],
            'items.*.markup_percentage'      => ['required', 'numeric', 'min:0', 'max:1000'],
            'items.*.condition_status'       => ['required', 'in:bueno,danado'],
            'items.*.notes'                  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required'         => 'Seleccione la Orden de Compra.',
            'warehouse_id.required'              => 'Seleccione el almacén de destino.',
            'items.required'                     => 'Ingrese al menos un ítem a recibir.',
            'items.*.product_id.required'        => 'Seleccione el producto del ítem.',
            'items.*.quantity_received.required' => 'Ingrese la cantidad recibida.',
            'items.*.unit_purchase_price.required' => 'Ingrese el precio unitario de compra.',
        ];
    }
}
