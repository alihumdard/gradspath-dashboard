<?php

namespace Modules\Settings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\app\Support\TimezoneOptions;

class UpdateMentorSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'mentor_type' => ['required', Rule::in(['graduate', 'professional'])],
            'title' => ['nullable', 'string', 'max:255'],
            'university_id' => [
                'nullable',
                'integer',
                Rule::exists('universities', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'university_program_id' => [
                'nullable',
                'integer',
                Rule::exists('university_programs', 'id')->where(function ($query) {
                    $query->where('is_active', true);

                    if ($this->filled('university_id')) {
                        $query->where('university_id', (int) $this->input('university_id'));
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'program_type' => ['nullable', Rule::in(['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])],
            'grad_school_display' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'office_hours_schedule' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'edu_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::requiredIf(fn() => $this->input('mentor_type') === 'graduate'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('mentor_type') === 'graduate' && is_string($value) && ! str_ends_with(strtolower($value), '.edu')) {
                        $fail('Graduate mentors must use a .edu email address.');
                    }
                },
            ],
            'calendly_link' => ['nullable', 'url:http,https', 'max:255'],
            'timezone' => ['nullable', Rule::in(TimezoneOptions::values())],
            'service_config_ids' => ['nullable', 'array'],
            'service_config_ids.*' => [
                'integer',
                Rule::exists('services_config', 'id')->where(fn($query) => $query->where('is_active', true)),
            ],
        ];
    }
}
