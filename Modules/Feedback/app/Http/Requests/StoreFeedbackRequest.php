<?php

namespace Modules\Feedback\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'stars' => ['required', 'integer', 'between:1,5'],
            'preparedness_rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
            'recommend' => ['nullable', 'boolean'],
            'service_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
