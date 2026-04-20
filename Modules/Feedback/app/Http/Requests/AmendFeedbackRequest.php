<?php

namespace Modules\Feedback\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
