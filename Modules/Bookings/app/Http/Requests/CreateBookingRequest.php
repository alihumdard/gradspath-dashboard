<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'guest_participants' => ['nullable', 'array', 'max:4'],
            'guest_participants.*.full_name' => ['required_with:guest_participants.*.email', 'nullable', 'string', 'max:255'],
            'guest_participants.*.email' => ['required_with:guest_participants.*.full_name', 'nullable', 'email', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sessionType = (string) $this->input('session_type');
            $expectedGuests = match ($sessionType) {
                '1on3' => 2,
                '1on5' => 4,
                default => 0,
            };

            $guestParticipants = collect($this->input('guest_participants', []))
                ->filter(function ($participant) {
                    if (!is_array($participant)) {
                        return false;
                    }

                    $name = trim((string) ($participant['full_name'] ?? ''));
                    $email = trim((string) ($participant['email'] ?? ''));

                    return $name !== '' || $email !== '';
                })
                ->values();

            if ($expectedGuests === 0 && $guestParticipants->isNotEmpty()) {
                $validator->errors()->add('guest_participants', 'Guest participants are only allowed for group bookings.');
                return;
            }

            if ($expectedGuests > 0 && $guestParticipants->count() !== $expectedGuests) {
                $validator->errors()->add(
                    'guest_participants',
                    "This booking requires {$expectedGuests} additional participant(s)."
                );
            }

            if ($guestParticipants->pluck('email')->filter()->count() !== $guestParticipants->pluck('email')->filter()->unique()->count()) {
                $validator->errors()->add('guest_participants', 'Each group participant must use a different email address.');
            }
        });
    }
}
