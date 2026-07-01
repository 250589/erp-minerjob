<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountingEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_date'              => ['required', 'date'],
            'description'             => ['nullable', 'string', 'max:255'],
            'details'                 => ['required', 'array', 'min:2'],
            'details.*.account_id'    => ['required', 'exists:chart_of_accounts,id'],
            'details.*.debit'         => ['required', 'numeric', 'min:0'],
            'details.*.credit'        => ['required', 'numeric', 'min:0'],
            'details.*.description'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $details   = $this->input('details', []);
            $totalDebit  = collect($details)->sum('debit');
            $totalCredit = collect($details)->sum('credit');

            if (abs($totalDebit - $totalCredit) >= 0.01) {
                $v->errors()->add(
                    'details',
                    "El asiento no cuadra: DEBE ({$totalDebit}) ≠ HABER ({$totalCredit}). Diferencia: " . abs($totalDebit - $totalCredit)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'entry_date.required'           => 'La fecha del asiento es obligatoria.',
            'details.required'              => 'El asiento debe tener al menos 2 líneas.',
            'details.min'                   => 'El asiento debe tener al menos 2 líneas (una en DEBE y una en HABER).',
            'details.*.account_id.required' => 'Seleccione la cuenta contable.',
        ];
    }
}
