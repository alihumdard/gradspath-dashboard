<?php

namespace Modules\Settings\app\Http\Requests;

use App\Rules\EduEmail;
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
            'university_id' => [
                'nullable',
                'integer',
                Rule::exists('universities', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'institution_text' => ['nullable', 'string', 'max:255', 'required_without:university_id'],
            'program_level' => [
                'nullable',
                Rule::in(['undergrad', 'grad', 'professional']),
                Rule::when(in_array($this->input('program_level'), ['undergrad', 'grad'], true), [
                    new EduEmail($this->user()?->email, 'Undergrad and grad students must use a .edu email address.'),
                ]),
            ],
            'program_type' => ['nullable', Rule::in(['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])],
            'timezone' => ['nullable', Rule::in(TimezoneOptions::values())],
        ];
    }
}
