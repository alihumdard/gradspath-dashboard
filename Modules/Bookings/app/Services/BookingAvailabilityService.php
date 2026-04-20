<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingAvailabilityService
{
    public function availableMonths(int $mentorId, int $serviceConfigId, string $sessionType): array
    {
        return $this->baseSlotQuery($mentorId, $serviceConfigId, $sessionType)
            ->orderBy('slot_date')
            ->get()
            ->pluck('slot_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth())
            ->unique(fn (CarbonInterface $month) => $month->format('Y-m'))
            ->values()
            ->map(fn (CarbonInterface $month) => [
                'month' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
            ])
            ->all();
    }

    public function availableDays(int $mentorId, int $serviceConfigId, string $sessionType, string $month): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $availableDates = $this->baseSlotQuery($mentorId, $serviceConfigId, $sessionType)
            ->whereBetween('slot_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('slot_date')
            ->get()
            ->pluck('slot_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        return [
            'month' => $monthStart->format('Y-m'),
            'label' => $monthStart->format('F Y'),
            'days' => $availableDates->map(fn (string $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('D j'),
                'weekday' => Carbon::parse($date)->format('D'),
                'day' => Carbon::parse($date)->format('j'),
            ])->all(),
        ];
    }

    public function availableTimes(int $mentorId, int $serviceConfigId, string $sessionType, string $date): array
    {
        $slots = $this->baseSlotQuery($mentorId, $serviceConfigId, $sessionType)
            ->whereDate('slot_date', $date)
            ->orderBy('start_time')
            ->get();

        return [
            'date' => Carbon::parse($date)->toDateString(),
            'label' => Carbon::parse($date)->format('l, F j, Y'),
            'times' => $slots->map(function (MentorAvailabilitySlot $slot) {
                $startsAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time, $slot->timezone ?: config('app.timezone'));

                return [
                    'slotId' => $slot->id,
                    'label' => $startsAt->format('g:i A'),
                    'startsAt' => $startsAt->toIso8601String(),
                    'timezone' => $slot->timezone,
                ];
            })->all(),
        ];
    }

    public function nextOfficeHourSessionForMentor(Mentor $mentor): ?array
    {
        $session = OfficeHourSession::query()
            ->with(['schedule.mentor.user:id,name', 'currentService:id,service_name,service_slug'])
            ->whereHas('schedule', fn (Builder $query) => $query->where('mentor_id', $mentor->id)->where('is_active', true))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->first();

        if (!$session) {
            return null;
        }

        return $this->transformOfficeHourSession($mentor, $session);
    }

    public function officeHoursDirectoryMentors(): array
    {
        $mentors = Mentor::query()
            ->with([
                'user:id,name',
                'university:id,name,display_name',
                'rating:id,mentor_id,avg_stars',
                'services' => fn ($query) => $query->where('services_config.is_active', true)->orderBy('services_config.sort_order'),
            ])
            ->where('status', 'active')
            ->get();

        $sessionsByMentor = $this->nextOfficeHourSessionsForMentors($mentors->pluck('id')->all());

        return $mentors
            ->map(function (Mentor $mentor) use ($sessionsByMentor) {
                $session = $sessionsByMentor->get($mentor->id);

                if (!$session) {
                    return null;
                }

                $sessionData = $this->transformOfficeHourSession($mentor, $session);

                return [
                    'id' => $mentor->id,
                    'mentorType' => $mentor->mentor_type === 'professional' ? 'Professionals' : 'Graduates',
                    'name' => $mentor->user?->name ?? 'Mentor',
                    'school' => $mentor->grad_school_display ?: $mentor->university?->display_name ?: $mentor->university?->name ?: 'School not listed',
                    'program' => $this->programLabel($mentor->program_type),
                    'programLabel' => $this->programLabel($mentor->program_type),
                    'rating' => (float) ($mentor->rating?->avg_stars ?? 5.0),
                    'officeHours' => $mentor->office_hours_schedule ?: $sessionData['sessionTime'],
                    'description' => $mentor->bio ?: $mentor->description ?: 'Mentor profile coming soon.',
                    'weeklyService' => $sessionData['weeklyService'],
                    'sessionTime' => $sessionData['sessionTime'],
                    'rotation' => $sessionData['rotation'],
                    'spotsFilled' => $sessionData['spotsFilled'],
                    'maxSpots' => $sessionData['maxSpots'],
                    'servicesOffered' => $mentor->services
                        ->where('is_office_hours', false)
                        ->pluck('service_name')
                        ->values()
                        ->all(),
                    'icon' => $this->programIcon($mentor->program_type),
                    'isBookable' => $sessionData['isBookable'],
                    'bookingUrl' => route('student.book-mentor', $mentor->id),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function nextOfficeHourSessionsForMentors(array $mentorIds): Collection
    {
        if ($mentorIds === []) {
            return collect();
        }

        return OfficeHourSession::query()
            ->with(['schedule.mentor.user:id,name', 'currentService:id,service_name,service_slug'])
            ->whereHas('schedule', fn (Builder $query) => $query
                ->whereIn('mentor_id', $mentorIds)
                ->where('is_active', true))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (OfficeHourSession $session) => (int) ($session->schedule?->mentor_id ?? 0))
            ->map(fn (Collection $sessions) => $sessions->first())
            ->forget(0);
    }

    private function transformOfficeHourSession(Mentor $mentor, OfficeHourSession $session): array
    {
        $startsAt = Carbon::parse($session->session_date->toDateString().' '.$session->start_time, $session->timezone ?: config('app.timezone'));
        $remaining = max(((int) $session->max_spots) - ((int) $session->current_occupancy), 0);

        return [
            'sessionId' => $session->id,
            'mentorName' => $mentor->user?->name ?? 'Mentor',
            'mentorMeta' => trim($this->programLabel($mentor->program_type).' • '.($mentor->grad_school_display ?: $mentor->university?->display_name ?: $mentor->university?->name ?: 'School not listed'), ' •'),
            'weeklyService' => $session->currentService?->service_name ?? 'Office Hours',
            'recurringTime' => $startsAt->format('l, g:i A T'),
            'meetingType' => 'Small Group Office Hours',
            'spotsFilled' => (int) $session->current_occupancy,
            'maxSpots' => (int) $session->max_spots,
            'remainingSpots' => $remaining,
            'isFull' => (bool) $session->is_full || $remaining === 0,
            'isBookable' => $session->status === 'upcoming' && !((bool) $session->is_full || $remaining === 0),
            'availabilityText' => $remaining === 0 ? 'Currently full' : ($remaining === 1 ? '1 spot remaining' : "{$remaining} spots remaining"),
            'sessionDate' => $session->session_date->toDateString(),
            'sessionTime' => $startsAt->format('l, g:i A T'),
            'rotation' => ucfirst((string) ($session->schedule?->frequency ?? 'weekly')),
            'serviceLocked' => (bool) $session->service_locked,
            'note' => $this->officeHoursNote($session),
        ];
    }

    private function baseSlotQuery(int $mentorId, int $serviceConfigId, string $sessionType): Builder
    {
        return MentorAvailabilitySlot::query()
            ->where('mentor_id', $mentorId)
            ->where('service_config_id', $serviceConfigId)
            ->where('session_type', $sessionType)
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->where(function (Builder $query) {
                $query->whereDate('slot_date', '>', now()->toDateString())
                    ->orWhere(function (Builder $todayQuery) {
                        $todayQuery->whereDate('slot_date', now()->toDateString())
                            ->whereTime('start_time', '>', now()->format('H:i:s'));
                    });
            })
            ->whereDoesntHave('bookings', fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']))
            ->where('is_booked', false);
    }

    private function officeHoursNote(OfficeHourSession $session): string
    {
        $serviceName = $session->currentService?->service_name ?? 'Office Hours';
        $occupancy = (int) $session->current_occupancy;

        if ($occupancy <= 1 && !$session->service_locked) {
            return "This week's office hours are currently set as {$serviceName}. If no one else joins by the cutoff, the first student may request another eligible service.";
        }

        return "This week's office hours are currently set as {$serviceName}. Because multiple students are booked, the session will stay focused on this designated service.";
    }

    private function programLabel(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'MBA',
            'law' => 'Law',
            'cmhc' => 'Counseling',
            'mft' => 'Marriage & Family Therapy',
            'msw' => 'Social Work',
            'clinical_psy' => 'Clinical Psychology',
            'therapy' => 'Therapy',
            'professional' => 'Professional',
            default => ucfirst(str_replace('_', ' ', (string) $programType ?: 'Mentor')),
        };
    }

    private function programIcon(?string $programType): string
    {
        return match ($programType) {
            'law' => 'law',
            'mba' => 'mba',
            default => 'therapy',
        };
    }
}
