<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Bookings\app\Services\ZoomService;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

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
            'mentor_availability_slot_id' => ['nullable', 'integer', 'exists:mentor_availability_slots,id'],
            'office_hour_session_id' => ['nullable', 'integer', 'exists:office_hour_sessions,id'],
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
            $mentor = Mentor::query()->with('user')->find((int) $this->input('mentor_id'));

            if ($sessionType === 'office_hours') {
                if (!$this->filled('office_hour_session_id')) {
                    $validator->errors()->add('office_hour_session_id', 'An office-hours session must be selected.');
                }

                if ($this->filled('mentor_availability_slot_id')) {
                    $validator->errors()->add('mentor_availability_slot_id', 'Office Hours cannot use a standard mentor availability slot.');
                }

                if ($guestParticipants->isNotEmpty()) {
                    $validator->errors()->add('guest_participants', 'Office Hours use fixed shared spots and cannot include group invitees.');
                }

                $hasActiveOfficeHours = $mentor?->officeHourSchedules()
                    ->where('is_active', true)
                    ->whereHas('mentor.services', fn ($query) => $query
                        ->where('services_config.is_active', true)
                        ->where('services_config.is_office_hours', true)
                        ->where('mentor_services.is_active', true))
                    ->exists() ?? false;

                if (! $hasActiveOfficeHours) {
                    $validator->errors()->add('booking', 'This mentor has not enabled Office Hours.');
                }

                if ($this->filled('office_hour_session_id') && $mentor) {
                    $sessionBelongsToMentor = OfficeHourSession::query()
                        ->whereKey((int) $this->input('office_hour_session_id'))
                        ->whereHas('schedule', fn ($query) => $query
                            ->where('mentor_id', $mentor->id)
                            ->where('is_active', true))
                        ->exists();

                    if (! $sessionBelongsToMentor) {
                        $validator->errors()->add('office_hour_session_id', 'Choose an active Office Hours session for this mentor.');
                    }
                }
            } else {
                if (!$this->filled('mentor_availability_slot_id')) {
                    $validator->errors()->add('mentor_availability_slot_id', 'Please choose an available time slot.');
                }

                if ($this->filled('office_hour_session_id')) {
                    $validator->errors()->add('office_hour_session_id', 'Standard bookings cannot use an office-hours session.');
                }
            }

            $service = $this->filled('service_config_id')
                ? ServiceConfig::query()->find((int) $this->input('service_config_id'))
                : null;

            if ($service) {
                if ($sessionType === 'office_hours' && ! (bool) $service->is_office_hours) {
                    $validator->errors()->add('service_config_id', 'Office Hours bookings must use the Office Hours service.');
                }

                if ($sessionType !== 'office_hours' && (bool) $service->is_office_hours) {
                    $validator->errors()->add('session_type', 'Choose Office Hours as the session type for the Office Hours service.');
                }
            }

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

            $emails = $guestParticipants
                ->pluck('email')
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter();

            if ($emails->count() !== $emails->unique()->count()) {
                $validator->errors()->add('guest_participants', 'Each group participant must use a different email address.');
            }

            if ($this->user()?->email && $emails->contains(strtolower((string) $this->user()->email))) {
                $validator->errors()->add('guest_participants', 'Do not enter your own email in the additional applicant fields.');
            }

            if (
                (string) $this->input('meeting_type', 'zoom') === 'zoom'
            ) {
                $zoom = app(ZoomService::class);

                if (! $zoom->isConfigured()) {
                    $validator->errors()->add('booking', 'Zoom booking is not configured right now.');
                } elseif (! $mentor || ! $zoom->hasConnectedMentor($mentor)) {
                    $validator->errors()->add('booking', 'This mentor must connect Zoom before students can book Zoom meetings.');
                }
            }
        });
    }
}
