<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'warehouse_id'              => ['required', 'exists:warehouses,id'],
            'area_id'                   => ['nullable', 'exists:areas,id'],
            'requirement_id'            => ['nullable', 'exists:requirements,id'],
            'notes'                     => ['nullable', 'string', 'max:500'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'exists:products,id'],
            'items.*.quantity_requested'=> ['required', 'numeric', 'min:0.0001'],
            'items.*.notes'             => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'              => 'Seleccione el almacén que entrega.',
            'items.required'                     => 'Agregue al menos un producto.',
            'items.*.product_id.required'        => 'Seleccione el producto.',
            'items.*.quantity_requested.required' => 'Ingrese la cantidad solicitada.',
        ];
    }
}