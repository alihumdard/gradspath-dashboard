<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_at' => ['required', 'date'],
            'session_timezone' => ['required', 'timezone'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:300'],
            'meeting_link' => ['nullable', 'url', 'max:2000'],
            'meeting_type' => ['required', Rule::in(['zoom', 'google_meet'])],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'no_show'])],
            'approval_status' => ['required', Rule::in(['not_required', 'pending', 'approved', 'rejected'])],
            'session_outcome' => ['required', Rule::in(['completed', 'no_show_student', 'no_show_mentor', 'interrupted', 'ended_early', 'unknown'])],
            'completion_source' => ['nullable', Rule::in(['schedule', 'zoom_event', 'manual'])],
            'session_outcome_note' => ['nullable', 'string', 'max:2000'],
            'admin_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
