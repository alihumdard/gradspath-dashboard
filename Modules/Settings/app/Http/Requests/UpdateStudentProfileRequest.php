<?php

namespace Modules\Settings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\app\Support\TimezoneOptions;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'university_id' => [
                'nullable',
                'integer',
                Rule::exists('universities', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'institution_text' => ['nullable', 'string', 'max:255', 'required_without:university_id'],
            'program_level' => ['nullable', Rule::in(['undergrad', 'grad', 'professional'])],
            'program_type' => ['nullable', Rule::in(['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])],
            'timezone' => ['nullable', Rule::in(TimezoneOptions::values())],
        ];
    }
}
