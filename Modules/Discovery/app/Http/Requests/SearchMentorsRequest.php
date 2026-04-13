<?php

namespace Modules\Discovery\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMentorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'mentor_type' => ['nullable', 'in:graduate,professional'],
            'program_type' => ['nullable', 'in:mba,law,therapy,cmhc,mft,msw,clinical_psy,other'],
            'university_id' => ['nullable', 'integer', 'exists:universities,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
