<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'         => ['required', 'in:student,mentor'],
            'program_level'=> ['required', 'in:undergrad,grad,professional'],
            'institution'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in'          => 'Please select either Student or Mentor.',
            'program_level.in' => 'Please select a valid program level.',
            'email.unique'     => 'An account with this email already exists.',
        ];
    }
}
