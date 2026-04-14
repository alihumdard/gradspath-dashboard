<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = mb_strtolower((string) $value);
                    $domain = str_contains($email, '@') ? explode('@', $email, 2)[1] : '';

                    if (!str_contains($domain, '.edu')) {
                        $fail('Please use a valid .edu email address.');
                    }
                },
                'unique:users,email',
            ],
            'password' => ['required', 'confirmed', Password::min(8)->letters()],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'program_level' => ['required', 'in:undergrad,grad,professional'],
            'institution' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Please select a valid role.',
            'program_level.in' => 'Please select a valid program level.',
            'email.unique' => 'An account with this email already exists.',
            'institution.required' => 'The institution field is required.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Registration validation failed.', [
            'email' => $this->input('email'),
            'role' => $this->input('role'),
            'program_level' => $this->input('program_level'),
            'institution' => $this->input('institution'),
            'errors' => $validator->errors()->toArray(),
        ]);

        parent::failedValidation($validator);
    }

    private function allowedRoles(): array
    {
        return config('auth-module.registration.allowed_roles', ['student', 'mentor']);
    }
}
