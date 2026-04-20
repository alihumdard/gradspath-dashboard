<?php

namespace Modules\Support\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
