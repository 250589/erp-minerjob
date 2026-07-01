<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'email', "unique:users,email,{$this->user->id}"],
            'password' => ['nullable', Password::min(8)],
            'area_id'  => ['nullable', 'exists:areas,id'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'status'   => ['required', 'in:activo,inactivo'],
            'role'     => ['required', 'exists:roles,name'],
        ];
    }
}