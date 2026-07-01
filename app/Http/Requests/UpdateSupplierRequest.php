<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')->id;

        return [
            'business_name'      => ['required', 'string', 'max:180'],
            'trade_name'         => ['nullable', 'string', 'max:180'],
            'tax_id'             => [
                'required', 'string', 'max:20',
                Rule::unique('suppliers', 'tax_id')->ignore($supplierId),
            ],
            'address'            => ['nullable', 'string', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:150'],
            'currency_default'   => ['required', 'string', 'size:3'],
            'payment_term_days'  => ['required', 'integer', 'min:0'],
            'bank_name'          => ['nullable', 'string', 'max:100'],
            'bank_account'       => ['nullable', 'string', 'max:40'],
            'status'             => ['required', 'in:activo,inactivo'],
            'contacts'           => ['nullable', 'array'],
            'contacts.*.name'    => ['required', 'string', 'max:150'],
            'contacts.*.position'=> ['nullable', 'string', 'max:100'],
            'contacts.*.phone'   => ['nullable', 'string', 'max:30'],
            'contacts.*.email'   => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required'   => 'La razón social es obligatoria.',
            'tax_id.required'          => 'El RUC es obligatorio.',
            'tax_id.unique'            => 'Ya existe otro proveedor con este RUC.',
            'contacts.*.name.required' => 'El nombre del contacto es obligatorio.',
        ];
    }
}
