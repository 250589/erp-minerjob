<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideApprovalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'   => ['required', 'in:aprobado,rechazado'],
            'comments' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Debe seleccionar Aprobar o Rechazar.',
            'status.in'       => 'Decisión inválida.',
        ];
    }
}
