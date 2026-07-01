<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'requirement_id' => ['required', 'exists:requirements,id'],
            'deadline_date'  => ['required', 'date', 'after:today'],
            'supplier_ids'   => ['required', 'array', 'min:1'],
            'supplier_ids.*' => ['exists:suppliers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'requirement_id.required' => 'Seleccione el requerimiento.',
            'deadline_date.required'  => 'La fecha límite es obligatoria.',
            'deadline_date.after'     => 'La fecha límite debe ser posterior a hoy.',
            'supplier_ids.required'   => 'Seleccione al menos un proveedor.',
            'supplier_ids.min'        => 'Seleccione al menos un proveedor.',
        ];
    }
}
