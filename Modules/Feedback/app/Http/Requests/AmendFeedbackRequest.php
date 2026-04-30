<?php

namespace Modules\Feedback\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmendFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback_id' => [Rule::requiredIf($this->route('id') === null), 'nullable', 'integer', 'exists:feedback,id'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_visible' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'manual_section' => ['nullable', 'string'],
        ];
    }
}
