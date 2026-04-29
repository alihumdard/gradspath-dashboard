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
    public function availableMonths(int $mentorId, int $serviceConfigId, string $sessionType, string $viewerTimezone = 'UTC'): array
    {
        if ($sessionType === 'office_hours') {
            $session = $this->nextGeneratedOfficeHourSession($mentorId);

            if (!$session) {
                return [];
            }

            $month = $this->sessionStartsAt($session)->setTimezone($viewerTimezone)->startOfMonth();

            return [[
                'month' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
            ]];
        }

        return $this->availableSlotCollection($mentorId, $serviceConfigId, $sessionType)
            ->map(fn (MentorAvailabilitySlot $slot) => $this->slotStartsAt($slot)->setTimezone($viewerTimezone)->copy()->startOfMonth())
            ->unique(fn (CarbonInterface $month) => $month->format('Y-m'))
            ->values()
            ->map(fn (CarbonInterface $month) => [
                'month' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
            ])
            ->all();
    }

    public function availableDays(int $mentorId, int $serviceConfigId, string $sessionType, string $month, string $viewerTimezone = 'UTC'): array
    {
        if ($sessionType === 'office_hours') {
            $session = $this->nextGeneratedOfficeHourSession($mentorId);
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $sessionStartsAt = $session ? $this->sessionStartsAt($session)->setTimezone($viewerTimezone) : null;

            if (!$session || $sessionStartsAt?->format('Y-m') !== $monthStart->format('Y-m')) {
                return [
                    'month' => $monthStart->format('Y-m'),
                    'label' => $monthStart->format('F Y'),
                    'days' => [],
                ];
            }

            $spotsFilled = $this->officeHourOccupancy($session);
            $remaining = max(((int) $session->max_spots) - $spotsFilled, 0);
            $isFull = $spotsFilled >= (int) $session->max_spots || $remaining === 0;

            return [
                'month' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('F Y'),
                'days' => [[
                    'date' => $sessionStartsAt->toDateString(),
                    'label' => $sessionStartsAt->format('D j'),
                    'weekday' => $sessionStartsAt->format('D'),
                    'day' => $sessionStartsAt->format('j'),
                    'sessionId' => (int) $session->id,
                    'spotsFilled' => $spotsFilled,
                    'maxSpots' => (int) $session->max_spots,
                    'remainingSpots' => $remaining,
                    'isFull' => $isFull,
                    'isBookable' => $session->status === 'upcoming' && !$isFull,
                ]],
            ];
        }

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $availableDates = $this->availableSlotCollection($mentorId, $serviceConfigId, $sessionType)
            ->map(fn (MentorAvailabilitySlot $slot) => $this->slotStartsAt($slot)->setTimezone($viewerTimezone))
            ->filter(fn (CarbonInterface $startsAt) => $startsAt->format('Y-m') === $monthStart->format('Y-m'))
            ->map(fn (CarbonInterface $startsAt) => $startsAt->toDateString())
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

    public function availableTimes(int $mentorId, int $serviceConfigId, string $sessionType, string $date, string $viewerTimezone = 'UTC'): array
    {
        if ($sessionType === 'office_hours') {
            $session = $this->nextGeneratedOfficeHourSession($mentorId);
            $selectedDate = Carbon::parse($date)->toDateString();
            $startsAt = $session ? $this->sessionStartsAt($session)->setTimezone($viewerTimezone) : null;

            if (!$session || $startsAt?->toDateString() !== $selectedDate) {
                return [
                    'date' => $selectedDate,
                    'label' => Carbon::parse($selectedDate)->format('l, F j, Y'),
                    'times' => [],
                ];
            }

            $durationMinutes = $this->officeHoursDuration($serviceConfigId);
            $endsAt = $startsAt->copy()->addMinutes($durationMinutes);
            $spotsFilled = $this->officeHourOccupancy($session);
            $remaining = max(((int) $session->max_spots) - $spotsFilled, 0);
            $isFull = $spotsFilled >= (int) $session->max_spots || $remaining === 0;
            $recurrenceLabel = match ((string) ($session->schedule?->frequency ?? 'weekly')) {
                'biweekly' => 'This is a recurring biweekly session',
                default => 'This is a recurring weekly session',
            };

            return [
                'date' => $selectedDate,
                'label' => Carbon::parse($selectedDate)->format('l, F j, Y'),
                'times' => [[
                    'sessionId' => (int) $session->id,
                    'sessionDate' => $selectedDate,
                    'startTime' => $startsAt->format('g:i A'),
                    'endTime' => $endsAt->format('g:i A'),
                    'timeRangeLabel' => $startsAt->format('g:i A').' to '.$endsAt->format('g:i A'),
                    'recurrenceLabel' => $recurrenceLabel,
                    'spotsFilled' => $spotsFilled,
                    'maxSpots' => (int) $session->max_spots,
                    'remainingSpots' => $remaining,
                    'availabilityText' => $remaining === 0 ? 'Currently full' : ($remaining === 1 ? '1 spot remaining' : "{$remaining} spots remaining"),
                    'isBookable' => $session->status === 'upcoming' && !$isFull,
                    'isFull' => $isFull,
                ]],
            ];
        }

        $slots = $this->availableSlotCollection($mentorId, $serviceConfigId, $sessionType)
            ->map(function (MentorAvailabilitySlot $slot) use ($viewerTimezone) {
                return [
                    'slot' => $slot,
                    'starts_at' => $this->slotStartsAt($slot)->setTimezone($viewerTimezone),
                ];
            })
            ->filter(fn (array $slotData) => $slotData['starts_at']->toDateString() === Carbon::parse($date)->toDateString())
            ->sortBy(fn (array $slotData) => $slotData['starts_at']->toIso8601String())
            ->values();

        return [
            'date' => Carbon::parse($date)->toDateString(),
            'label' => Carbon::parse($date)->format('l, F j, Y'),
            'times' => $slots->map(function (array $slotData) use ($viewerTimezone) {
                /** @var MentorAvailabilitySlot $slot */
                $slot = $slotData['slot'];
                /** @var CarbonInterface $startsAt */
                $startsAt = $slotData['starts_at'];
                return [
                    'slotId' => $slot->id,
                    'label' => $startsAt->format('g:i A'),
                    'startsAt' => $startsAt->toIso8601String(),
                    'timezone' => $viewerTimezone,
                ];
            })->all(),
        ];
    }

    public function nextOfficeHourSessionForMentor(Mentor $mentor, string $viewerTimezone = 'UTC'): ?array
    {
        if (!$this->mentorHasOfficeHoursService($mentor->id)) {
            return null;
        }

        $session = OfficeHourSession::query()
            ->with(['schedule.currentService:id,service_name,service_slug', 'schedule.mentor.user:id,name', 'currentService:id,service_name,service_slug'])
            ->withCount([
                'bookings as active_bookings_count' => fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->whereHas('schedule', fn (Builder $query) => $query->where('mentor_id', $mentor->id)->where('is_active', true))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->first();

        if (!$session) {
            return null;
        }

        return $this->transformOfficeHourSession($mentor, $session, $viewerTimezone);
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

                $sessionData = $this->transformOfficeHourSession($mentor, $session, $session->timezone ?: 'UTC');

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
                    'bookingUrl' => route('student.book-mentor', [
                        'id' => $mentor->id,
                        'service' => 'office_hours',
                    ]),
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
            ->with(['schedule.currentService:id,service_name,service_slug', 'schedule.mentor.user:id,name', 'currentService:id,service_name,service_slug'])
            ->withCount([
                'bookings as active_bookings_count' => fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->whereHas('schedule', fn (Builder $query) => $query
                ->whereIn('mentor_id', $mentorIds)
                ->where('is_active', true)
                ->whereHas('mentor.services', fn (Builder $serviceQuery) => $serviceQuery
                    ->where('services_config.is_active', true)
                    ->where('services_config.is_office_hours', true)
                    ->where('mentor_services.is_active', true)))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (OfficeHourSession $session) => (int) ($session->schedule?->mentor_id ?? 0))
            ->map(fn (Collection $sessions) => $sessions->first())
            ->forget(0);
    }

    private function nextGeneratedOfficeHourSession(int $mentorId): ?OfficeHourSession
    {
        if (!$this->mentorHasOfficeHoursService($mentorId)) {
            return null;
        }

        return OfficeHourSession::query()
            ->with(['schedule.currentService:id,service_name,service_slug', 'currentService:id,service_name,service_slug'])
            ->withCount([
                'bookings as active_bookings_count' => fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->whereHas('schedule', fn (Builder $query) => $query->where('mentor_id', $mentorId)->where('is_active', true))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->first();
    }

    private function mentorHasOfficeHoursService(int $mentorId): bool
    {
        return Mentor::query()
            ->whereKey($mentorId)
            ->whereHas('services', fn (Builder $query) => $query
                ->where('services_config.is_active', true)
                ->where('services_config.is_office_hours', true)
                ->where('mentor_services.is_active', true))
            ->exists();
    }

    private function transformOfficeHourSession(Mentor $mentor, OfficeHourSession $session, string $viewerTimezone = 'UTC'): array
    {
        $activeBookings = $this->officeHourOccupancy($session);
        $startsAt = $activeBookings === 0 && $session->schedule
            ? $this->nextScheduleStart($session->schedule)->setTimezone($viewerTimezone)
            : $this->sessionStartsAt($session)->setTimezone($viewerTimezone);
        $maxSpots = (int) ($session->schedule?->max_spots ?: $session->max_spots ?: 3);
        $remaining = max($maxSpots - $activeBookings, 0);
        $serviceName = $activeBookings === 0
            ? ($session->schedule?->currentService?->service_name ?? $session->currentService?->service_name ?? 'Office Hours')
            : ($session->currentService?->service_name ?? $session->schedule?->currentService?->service_name ?? 'Office Hours');

        return [
            'sessionId' => $session->id,
            'mentorName' => $mentor->user?->name ?? 'Mentor',
            'mentorMeta' => trim($this->programLabel($mentor->program_type).' • '.($mentor->grad_school_display ?: $mentor->university?->display_name ?: $mentor->university?->name ?: 'School not listed'), ' •'),
            'weeklyService' => $serviceName,
            'recurringTime' => $startsAt->format('l, g:i A T'),
            'meetingType' => 'Small Group Office Hours',
            'spotsFilled' => $activeBookings,
            'maxSpots' => $maxSpots,
            'remainingSpots' => $remaining,
            'isFull' => $activeBookings >= $maxSpots || $remaining === 0,
            'isBookable' => $session->status === 'upcoming' && $remaining > 0,
            'availabilityText' => $remaining === 0 ? 'Currently full' : ($remaining === 1 ? '1 spot remaining' : "{$remaining} spots remaining"),
            'sessionDate' => $startsAt->toDateString(),
            'sessionTime' => $startsAt->format('l, g:i A T'),
            'rotation' => ucfirst((string) ($session->schedule?->frequency ?? 'weekly')),
            'serviceLocked' => (bool) $session->service_locked,
            'note' => $this->officeHoursNote($serviceName, $activeBookings, (bool) $session->service_locked),
        ];
    }

    private function baseSlotQuery(int $mentorId, int $serviceConfigId, string $sessionType): Builder
    {
        return MentorAvailabilitySlot::query()
            ->where('mentor_id', $mentorId)
            ->where('session_type', $sessionType)
            ->where('service_config_id', $serviceConfigId)
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->whereDoesntHave('bookings', fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']))
            ->where('is_booked', false);
    }

    private function availableSlotCollection(int $mentorId, int $serviceConfigId, string $sessionType): Collection
    {
        return $this->baseSlotQuery($mentorId, $serviceConfigId, $sessionType)
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (MentorAvailabilitySlot $slot) => $this->slotStartsAt($slot)->utc()->isFuture())
            ->values();
    }

    private function slotStartsAt(MentorAvailabilitySlot $slot): Carbon
    {
        if ($slot->starts_at_utc) {
            return $slot->starts_at_utc->copy()->utc();
        }

        return Carbon::parse(
            $slot->slot_date->toDateString().' '.$slot->start_time,
            $slot->timezone ?: config('app.timezone', 'UTC')
        );
    }

    private function sessionStartsAt(OfficeHourSession $session): Carbon
    {
        return Carbon::parse(
            $session->session_date->toDateString().' '.$session->start_time,
            $session->timezone ?: config('app.timezone', 'UTC')
        );
    }

    private function officeHoursNote(string $serviceName, int $occupancy, bool $serviceLocked): string
    {
        if ($occupancy <= 1 && !$serviceLocked) {
            return "This week's office hours are currently set as {$serviceName}. If no one else joins by the cutoff, the first student may request another eligible service.";
        }

        return "This week's office hours are currently set as {$serviceName}. Because multiple students are booked, the session will stay focused on this designated service.";
    }

    private function officeHourOccupancy(OfficeHourSession $session): int
    {
        $activeBookings = (int) ($session->active_bookings_count ?? 0);
        $storedOccupancy = (int) $session->current_occupancy;

        if ($activeBookings > 0) {
            return $activeBookings;
        }

        return $storedOccupancy > 1 ? $storedOccupancy : 0;
    }

    private function nextScheduleStart($schedule): Carbon
    {
        $weekdayIndexes = [
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
        ];
        $timezone = $schedule->timezone ?: config('app.timezone', 'UTC');
        $now = now($timezone);
        $target = $now->copy()->startOfDay();
        $targetDay = $weekdayIndexes[(string) $schedule->day_of_week] ?? 0;
        $target->addDays(($targetDay - (int) $target->dayOfWeek + 7) % 7);

        [$hour, $minute, $second] = array_pad(array_map('intval', explode(':', substr((string) $schedule->start_time, 0, 8))), 3, 0);
        $target->setTime($hour, $minute, $second);

        if ($target->lte($now)) {
            $target->addWeek();
        }

        return $target;
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

    private function officeHoursDuration(int $serviceConfigId): int
    {
        $service = ServiceConfig::query()->find($serviceConfigId);

        return max((int) ($service?->duration_minutes ?? 45), 1);
    }
}
