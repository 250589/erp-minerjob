<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        return [
            'category_id'       => ['nullable', 'exists:product_categories,id'],
            'unit_id'           => ['required', 'exists:units_of_measure,id'],
            'sku'               => ['required', 'string', 'max:50', "unique:products,sku,{$productId}"],
            'name'              => ['required', 'string', 'max:180'],
            'description'       => ['nullable', 'string'],
            'min_stock'         => ['nullable', 'numeric', 'min:0'],
            'max_stock'         => ['nullable', 'numeric', 'min:0'],
            'markup_percentage' => ['required', 'numeric', 'min:0', 'max:1000'],
            'status'            => ['required', 'in:activo,inactivo'],
        ];
    }
}