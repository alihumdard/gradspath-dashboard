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

    protected function prepareForValidation(): void
    {
        if ($this->input('role') !== 'mentor') {
            return;
        }

        $legacyProgramLevel = $this->input('program_level');

        if (! $this->filled('mentor_type') && in_array($legacyProgramLevel, ['grad', 'professional'], true)) {
            $this->merge([
                'mentor_type' => $legacyProgramLevel === 'professional' ? 'professional' : 'graduate',
                'program_level' => null,
            ]);

            return;
        }

        if (in_array($legacyProgramLevel, ['grad', 'professional'], true)) {
            $this->merge(['program_level' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['required', 'confirmed', Password::min(8)->letters()],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'program_level' => [
                Rule::requiredIf(fn () => $this->input('role') === 'student'),
                'nullable',
                Rule::in(['undergrad']),
            ],
            'mentor_type' => [
                Rule::requiredIf(fn () => $this->input('role') === 'mentor'),
                'nullable',
                Rule::in(['graduate', 'professional']),
            ],
            'institution_id' => ['nullable', 'integer', Rule::exists('universities', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'institution' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Please select a valid role.',
            'program_level.required' => 'Please select a valid student level.',
            'program_level.in' => 'Students can only select the student level during signup.',
            'mentor_type.required' => 'Please select a valid mentor type.',
            'mentor_type.in' => 'Please select either a graduate or professional mentor type.',
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
            'mentor_type' => $this->input('mentor_type'),
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
