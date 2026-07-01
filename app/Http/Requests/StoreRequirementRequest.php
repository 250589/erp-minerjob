<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id'                             => ['nullable', 'exists:areas,id'],
            'justification'                       => ['nullable', 'string', 'max:1000'],
            'required_date'                       => ['nullable', 'date', 'after:today'],
            'items'                               => ['required', 'array', 'min:1'],
            'items.*.product_id'                  => ['nullable', 'exists:products,id'],
            'items.*.unit_id'                     => ['required', 'exists:units_of_measure,id'],
            'items.*.description'                 => ['required', 'string', 'max:255'],
            'items.*.quantity'                    => ['required', 'numeric', 'min:0.0001'],
            'items.*.estimated_unit_price'        => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                      => 'Debe agregar al menos un ítem.',
            'items.min'                           => 'Debe agregar al menos un ítem.',
            'items.*.description.required'        => 'La descripción del ítem :position es obligatoria.',
            'items.*.unit_id.required'            => 'La unidad de medida del ítem :position es obligatoria.',
            'items.*.quantity.required'           => 'La cantidad del ítem :position es obligatoria.',
            'items.*.quantity.min'                => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
