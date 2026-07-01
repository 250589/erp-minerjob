<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'area_id'  => ['nullable', 'exists:areas,id'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['required', 'exists:roles,name'],
        ];
    }
}