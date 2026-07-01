<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;
        return [
            'code'                => ['required', 'string', 'max:30', "unique:warehouses,code,{$warehouseId}"],
            'name'                => ['required', 'string', 'max:150'],
            'type'                => ['required', 'in:principal,subalmacen,transito'],
            'parent_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'manager_user_id'     => ['nullable', 'exists:users,id'],
            'address'             => ['nullable', 'string', 'max:255'],
            'status'              => ['required', 'in:activo,inactivo'],
        ];
    }
}