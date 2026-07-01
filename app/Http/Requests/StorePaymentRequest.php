<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payment_date'        => ['required', 'date'],
            'method'              => ['required', 'in:transferencia,deposito,cheque'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'currency'            => ['required', 'string', 'size:3'],
            'exchange_rate'       => ['required', 'numeric', 'min:0.0001'],
            'origin_account'      => ['nullable', 'string', 'max:40'],
            'destination_account' => ['nullable', 'string', 'max:40'],
            'reference_number'    => ['nullable', 'string', 'max:60'],
            'notes'               => ['nullable', 'string', 'max:500'],
            'voucher_file'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_date.required'  => 'La fecha de pago es obligatoria.',
            'method.required'        => 'El método de pago es obligatorio.',
            'amount.required'        => 'El monto es obligatorio.',
            'voucher_file.mimes'     => 'El voucher debe ser PDF, JPG o PNG.',
            'voucher_file.max'       => 'El voucher no puede superar los 5 MB.',
        ];
    }
}
