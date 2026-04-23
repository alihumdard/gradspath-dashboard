<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'session_outcome' => ['required', Rule::in(['completed', 'no_show_student', 'no_show_mentor', 'interrupted', 'ended_early', 'unknown'])],
            'session_outcome_note' => ['nullable', 'string', 'max:2000'],
            'completion_source' => ['nullable', Rule::in(['schedule', 'zoom_event', 'manual'])],
            'manual_section' => ['nullable', 'string'],
        ];
    }
}
