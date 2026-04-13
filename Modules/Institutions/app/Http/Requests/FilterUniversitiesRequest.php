<?php

namespace Modules\Institutions\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterUniversitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'tier' => ['nullable', 'in:elite,top,regional'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
