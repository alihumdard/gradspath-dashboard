<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mentor_id' => ['required', 'integer', 'exists:mentors,id'],
            'service_config_id' => ['required', 'integer', 'exists:services_config,id'],
            'session_type' => ['required', 'in:1on1,1on3,1on5,office_hours'],
            'session_at' => ['required', 'date', 'after:now'],
            'session_timezone' => ['nullable', 'string', 'max:80'],
            'meeting_type' => ['nullable', 'in:zoom,google_meet'],
        ];
    }
}
