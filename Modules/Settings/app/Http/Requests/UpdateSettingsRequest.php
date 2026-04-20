<?php

namespace Modules\Settings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['nullable', 'in:light,dark'],
            'email_notifications' => ['nullable', 'boolean'],
            'sms_notifications' => ['nullable', 'boolean'],
        ];
    }
}
