<?php

namespace Modules\Institutions\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniversityProgramRequest extends FormRequest
{
    private const PROGRAM_TYPES = [
        'mba',
        'law',
        'therapy',
        'cmhc',
        'mft',
        'msw',
        'clinical_psy',
        'other',
    ];

    private const TIERS = [
        'elite',
        'top',
        'regional',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', 'exists:universities,id'],
            'program_name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', 'string', Rule::in(self::PROGRAM_TYPES)],
            'tier' => ['required', 'string', Rule::in(self::TIERS)],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'manual_station' => ['nullable', 'string'],
        ];
    }
}
