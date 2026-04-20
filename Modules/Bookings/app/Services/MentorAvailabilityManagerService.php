<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\MentorAvailabilityRule;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Settings\app\Models\Mentor;

class MentorAvailabilityManagerService
{
    public const WINDOW_WEEKS = 12;

    private const WEEKDAY_LABELS = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    private const OFFICE_HOURS_MAX_SPOTS = 3;

    public function formData(Mentor $mentor): array
    {
        $slots = $this->directSlotsQuery($mentor)
            ->withCount([
                'bookings as active_bookings_count' => fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->with([
                'service:id,service_name',
                'bookings' => fn ($query) => $query
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->with(['booker:id,name,email', 'service:id,service_name'])
                    ->orderBy('session_at'),
            ])
            ->where('is_active', true)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->get()
            ->sortBy(fn (MentorAvailabilitySlot $slot) => $slot->slot_date->toDateString().' '.$slot->start_time)
            ->groupBy(fn (MentorAvailabilitySlot $slot) => $slot->slot_date->toDateString());

        $firstSlot = $slots->flatten(1)->first();

        return [
            'timezone' => $firstSlot?->timezone ?: 'America/New_York',
            'effective_from' => null,
            'effective_until' => null,
            'date_slots' => $slots
                ->map(function ($daySlots, string $date) {
                    $dateCarbon = Carbon::parse($date);

                    return [
                        'date' => $date,
                        'label' => $dateCarbon->format('l'),
                        'enabled' => $daySlots->isNotEmpty(),
                        'slot_count' => $daySlots->count(),
                        'booked_count' => $daySlots->sum(fn (MentorAvailabilitySlot $slot) => (int) ($slot->active_bookings_count ?? 0)),
                        'bookings' => $daySlots
                            ->flatMap(function (MentorAvailabilitySlot $slot) {
                                return $slot->bookings->map(function ($booking) use ($slot) {
                                    return [
                                        'id' => (int) $booking->id,
                                        'booker_name' => $booking->booker?->name ?? 'Booked user',
                                        'booker_email' => $booking->booker?->email,
                                        'service_name' => $booking->service?->service_name ?? $slot->service?->service_name ?? 'Service',
                                        'slot_label' => $this->formatTimeRange((string) $slot->start_time, (string) $slot->end_time),
                                        'booked_at_label' => $booking->created_at?->setTimezone($booking->session_timezone ?: config('app.timezone', 'UTC'))->format('M j, Y g:i A'),
                                        'session_label' => $booking->session_at?->setTimezone($booking->session_timezone ?: config('app.timezone', 'UTC'))->format('M j, Y g:i A'),
                                        'status' => (string) $booking->status,
                                    ];
                                });
                            })
                            ->sortBy('session_label')
                            ->values()
                            ->all(),
                        'slots' => $daySlots
                            ->map(fn (MentorAvailabilitySlot $slot) => [
                                'slot_id' => (int) $slot->id,
                                'start_time' => substr((string) $slot->start_time, 0, 5),
                                'end_time' => substr((string) $slot->end_time, 0, 5),
                                'service_config_id' => $slot->service_config_id,
                                'is_booked' => (int) ($slot->active_bookings_count ?? 0) > 0,
                                'booking_count' => (int) ($slot->active_bookings_count ?? 0),
                                'summary' => $this->formatTimeRange((string) $slot->start_time, (string) $slot->end_time),
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function insights(Mentor $mentor): array
    {
        $slots = $this->directSlotsQuery($mentor)
            ->where('is_active', true)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->get()
            ->sortBy(fn (MentorAvailabilitySlot $slot) => $slot->slot_date->toDateString().' '.$slot->start_time)
            ->groupBy(fn (MentorAvailabilitySlot $slot) => $slot->slot_date->toDateString());

        $scheduledMinutes = $slots->flatten(1)
            ->reduce(function (int $minutes, MentorAvailabilitySlot $slot) {
                if (!$slot->start_time || !$slot->end_time) {
                    return $minutes;
                }

                $start = Carbon::createFromFormat('H:i:s', (string) $slot->start_time);
                $end = Carbon::createFromFormat('H:i:s', (string) $slot->end_time);

                return $minutes + $start->diffInMinutes($end);
            }, 0);

        $windowEnd = now()->copy()->addWeeks(self::WINDOW_WEEKS)->endOfDay();
        $openSlotsQuery = $this->directSlotsQuery($mentor)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->whereDate('slot_date', '<=', $windowEnd->toDateString())
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->whereDoesntHave('bookings', fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']));

        $openSlotsCount = (clone $openSlotsQuery)->count();
        $nextOpenSlot = (clone $openSlotsQuery)
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->first();

        return [
            'active_days' => $slots->filter(fn ($daySlots) => $daySlots->isNotEmpty())->count(),
            'weekly_hours' => $this->formatMinutesAsHours($scheduledMinutes),
            'window_weeks' => self::WINDOW_WEEKS,
            'open_slots_count' => $openSlotsCount,
            'next_open_slot' => $nextOpenSlot ? $this->formatSlotLabel($nextOpenSlot) : 'No open slots generated yet',
            'effective_range' => 'Dates stay active until you remove them',
            'preview_days' => $slots
                ->map(function ($daySlots, string $date) {
                    $dateCarbon = Carbon::parse($date);

                    return [
                        'label' => $dateCarbon->format('D, M j'),
                        'summary' => $daySlots
                            ->map(fn (MentorAvailabilitySlot $slot) => $this->formatTimeRange((string) $slot->start_time, (string) $slot->end_time))
                            ->implode(', '),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function schedulerPayload(array $formData, array $insights, array $timezoneOptions, array $serviceOptions = []): array
    {
        $today = now();

        return [
            'timezone' => (string) ($formData['timezone'] ?? 'America/New_York'),
            'effective_from' => null,
            'effective_until' => null,
            'window_weeks' => (int) ($insights['window_weeks'] ?? self::WINDOW_WEEKS),
            'today' => $today->toDateString(),
            'calendar_month' => [
                'year' => (int) $today->year,
                'month' => (int) $today->month,
                'label' => $today->format('F Y'),
            ],
            'weekday_order' => ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
            'insights' => $insights,
            'timezone_options' => collect($timezoneOptions)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'service_options' => array_values($serviceOptions),
            'time_options' => $this->timeOptions(),
            'date_slots' => collect($formData['date_slots'] ?? [])
                ->map(function (array $row) {
                    $slots = collect($row['slots'] ?? [])
                        ->filter(fn ($slot) => is_array($slot))
                        ->map(function (array $slot) {
                            $startTime = (string) ($slot['start_time'] ?? '');
                            $endTime = (string) ($slot['end_time'] ?? '');

                            return [
                                'slot_id' => isset($slot['slot_id']) ? (int) $slot['slot_id'] : null,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'service_config_id' => isset($slot['service_config_id']) ? (int) $slot['service_config_id'] : null,
                                'is_booked' => !empty($slot['is_booked']),
                                'booking_count' => isset($slot['booking_count']) ? (int) $slot['booking_count'] : 0,
                                'start_index' => $this->timeToIndex($startTime),
                                'end_index' => $this->timeToIndex($endTime),
                                'summary' => $startTime !== '' && $endTime !== ''
                                    ? $this->formatTimeRange($startTime, $endTime)
                                    : null,
                            ];
                        })
                        ->values()
                        ->all();

                    return [
                        'key' => (string) ($row['date'] ?? ''),
                        'label' => (string) ($row['label'] ?? ''),
                        'enabled' => !empty($row['enabled']),
                        'slot_count' => isset($row['slot_count']) ? (int) $row['slot_count'] : count($slots),
                        'booked_count' => isset($row['booked_count']) ? (int) $row['booked_count'] : 0,
                        'bookings' => collect($row['bookings'] ?? [])
                            ->filter(fn ($booking) => is_array($booking))
                            ->values()
                            ->all(),
                        'slots' => $slots,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function timeOptions(): array
    {
        return collect(range(0, 47))
            ->map(function (int $index) {
                $hour = intdiv($index, 2);
                $minute = $index % 2 === 0 ? 0 : 30;
                $value = sprintf('%02d:%02d', $hour, $minute);

                return [
                    'value' => $value,
                    'label' => Carbon::createFromTime($hour, $minute)->format('g:i A'),
                    'index' => $index,
                ];
            })
            ->all();
    }

    public function updateWeeklyAvailability(Mentor $mentor, array $payload): void
    {
        DB::transaction(function () use ($mentor, $payload) {
            $ruleIds = $this->directRulesQuery($mentor)->pluck('id');

            if ($ruleIds->isNotEmpty()) {
                $this->deleteFutureUnbookedSlotsForRuleIds($ruleIds->all());
            }

            $this->deleteFutureUnbookedDirectSlots($mentor);
            $this->directRulesQuery($mentor)->delete();

            foreach ($payload['date_slots'] ?? [] as $dateSlot) {
                $slotDate = isset($dateSlot['date']) ? Carbon::parse($dateSlot['date'])->startOfDay() : null;

                if (!$slotDate || $slotDate->lt(now()->startOfDay())) {
                    continue;
                }

                $enabled = filter_var($dateSlot['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $slots = $this->normalizeSlotsPayload($dateSlot);

                if (!$enabled || $slots === []) {
                    continue;
                }

                foreach ($slots as $slot) {
                    if (!empty($slot['is_booked'])) {
                        continue;
                    }

                    MentorAvailabilitySlot::query()->updateOrCreate([
                        'mentor_id' => $mentor->id,
                        'slot_date' => $slotDate->toDateString(),
                        'start_time' => $this->normalizeTime((string) $slot['start_time']),
                        'session_type' => '1on1',
                    ], [
                        'availability_rule_id' => null,
                        'mentor_id' => $mentor->id,
                        'service_config_id' => (int) $slot['service_config_id'],
                        'slot_date' => $slotDate->toDateString(),
                        'start_time' => $this->normalizeTime((string) $slot['start_time']),
                        'end_time' => $this->normalizeTime((string) $slot['end_time']),
                        'timezone' => (string) ($payload['timezone'] ?? 'America/New_York'),
                        'session_type' => '1on1',
                        'max_participants' => 1,
                        'booked_participants_count' => 0,
                        'is_booked' => false,
                        'is_blocked' => false,
                        'is_active' => true,
                        'notes' => 'Saved from mentor date availability',
                    ]);
                }
            }
        });
    }

    public function officeHoursConfig(Mentor $mentor): array
    {
        $schedule = $mentor->officeHourSchedules()
            ->with('currentService:id,service_name')
            ->latest('id')
            ->first();
        $serviceOptions = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get(['services_config.id', 'services_config.service_name'])
            ->map(fn ($service) => [
                'value' => (int) $service->id,
                'label' => $service->service_name,
            ])
            ->values()
            ->all();

        return [
            'enabled' => (bool) ($schedule?->is_active ?? false),
            'schedule_id' => $schedule?->id,
            'service_config_id' => $schedule?->current_service_id ? (int) $schedule->current_service_id : null,
            'service_name' => $schedule?->currentService?->service_name,
            'day_of_week' => (string) ($schedule?->day_of_week ?? 'sun'),
            'start_time' => $schedule?->start_time ? substr((string) $schedule->start_time, 0, 5) : '20:00',
            'timezone' => (string) ($schedule?->timezone ?? 'America/New_York'),
            'frequency' => (string) ($schedule?->frequency ?? 'weekly'),
            'max_spots' => self::OFFICE_HOURS_MAX_SPOTS,
            'meeting_type' => 'Small Group Office Hours',
            'weekday_options' => collect(self::WEEKDAY_LABELS)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'frequency_options' => [
                ['value' => 'weekly', 'label' => 'Weekly'],
                ['value' => 'biweekly', 'label' => 'Biweekly'],
            ],
            'service_options' => $serviceOptions,
        ];
    }

    public function officeHoursPreview(Mentor $mentor, ?array $config = null): array
    {
        $config ??= $this->officeHoursConfig($mentor);

        $schedule = $mentor->officeHourSchedules()
            ->with('currentService:id,service_name')
            ->latest('id')
            ->first();

        $session = OfficeHourSession::query()
            ->with(['currentService:id,service_name', 'schedule'])
            ->whereHas('schedule', fn (Builder $query) => $query->where('mentor_id', $mentor->id)->where('is_active', true))
            ->where('status', 'upcoming')
            ->whereDate('session_date', '>=', now()->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->first();

        if ($session) {
            $startsAt = Carbon::parse($session->session_date->toDateString().' '.$session->start_time, $session->timezone ?: config('app.timezone'));
            $remaining = max(((int) $session->max_spots) - ((int) $session->current_occupancy), 0);

            return [
                'mentor_name' => $mentor->user?->name ?? 'Mentor',
                'mentor_meta' => trim(($this->programLabel($mentor->program_type) ?: 'Mentor').' • '.($mentor->grad_school_display ?: 'School not listed'), ' •'),
                'spots_filled' => (int) $session->current_occupancy,
                'max_spots' => (int) $session->max_spots,
                'spots_badge' => $session->current_occupancy.'/'.$session->max_spots.' spots filled',
                'weekly_service' => $session->currentService?->service_name ?? $config['service_name'] ?? 'Office Hours',
                'recurring_time' => $startsAt->format('l, g:i A T'),
                'meeting_type' => 'Small Group Office Hours',
                'availability_text' => $remaining === 0 ? 'Currently full' : ($remaining === 1 ? '1 spot remaining' : "{$remaining} spots remaining"),
                'note' => $this->officeHoursNote(
                    $session->currentService?->service_name ?? $config['service_name'] ?? 'Office Hours',
                    (int) $session->current_occupancy,
                    (bool) $session->service_locked
                ),
                'has_upcoming_session' => true,
                'service_locked' => (bool) $session->service_locked,
            ];
        }

        $timezone = (string) ($config['timezone'] ?? 'America/New_York');
        $startTime = (string) ($config['start_time'] ?? '');
        $weeklyService = (string) ($config['service_name'] ?? 'Office Hours');
        $recurringTime = 'Schedule coming soon';

        if ($config['enabled'] ?? false) {
            $dayLabel = self::WEEKDAY_LABELS[(string) ($config['day_of_week'] ?? '')] ?? 'Weekly';

            if ($startTime !== '') {
                $recurringTime = $dayLabel.', '.$this->formatTimeLabel($startTime).' '.$this->timezoneAbbreviation($timezone);
            } else {
                $recurringTime = $dayLabel.' schedule pending';
            }
        }

        return [
            'mentor_name' => $mentor->user?->name ?? 'Mentor',
            'mentor_meta' => trim(($this->programLabel($mentor->program_type) ?: 'Mentor').' • '.($mentor->grad_school_display ?: 'School not listed'), ' •'),
            'spots_filled' => 0,
            'max_spots' => self::OFFICE_HOURS_MAX_SPOTS,
            'spots_badge' => '0/'.self::OFFICE_HOURS_MAX_SPOTS.' spots filled',
            'weekly_service' => $weeklyService,
            'recurring_time' => $recurringTime,
            'meeting_type' => 'Small Group Office Hours',
            'availability_text' => ($config['enabled'] ?? false) ? 'No upcoming session generated yet' : 'Office hours are currently off',
            'note' => ($config['enabled'] ?? false)
                ? "This week's office hours are currently set as {$weeklyService}. Once an upcoming session is generated, spots and service-lock details will appear here."
                : 'Turn on office hours to publish one recurring weekly or biweekly session for this mentor.',
            'has_upcoming_session' => false,
            'service_locked' => false,
        ];
    }

    public function saveOfficeHours(Mentor $mentor, array $payload): void
    {
        DB::transaction(function () use ($mentor, $payload) {
            $enabled = (bool) ($payload['enabled'] ?? false);

            $schedule = $mentor->officeHourSchedules()->latest('id')->first();

            if (!$schedule && !$enabled) {
                $mentor->forceFill(['office_hours_schedule' => null])->save();

                return;
            }

            $attributes = [
                'current_service_id' => $enabled ? (int) $payload['service_config_id'] : null,
                'day_of_week' => (string) ($payload['day_of_week'] ?? 'sun'),
                'start_time' => $this->normalizeTime((string) ($payload['start_time'] ?? '20:00')),
                'timezone' => (string) ($payload['timezone'] ?? 'America/New_York'),
                'frequency' => (string) ($payload['frequency'] ?? 'weekly'),
                'max_spots' => self::OFFICE_HOURS_MAX_SPOTS,
                'is_active' => $enabled,
            ];

            if ($schedule) {
                $schedule->fill($attributes)->save();
            } else {
                $schedule = $mentor->officeHourSchedules()->create($attributes);
            }

            $mentor->forceFill([
                'office_hours_schedule' => $enabled ? $this->formatOfficeHoursSummary($attributes['day_of_week'], $attributes['start_time'], $attributes['timezone'], $attributes['frequency']) : null,
            ])->save();

            if ($enabled && !empty($attributes['current_service_id'])) {
                OfficeHourSession::query()
                    ->where('schedule_id', $schedule->id)
                    ->where('status', 'upcoming')
                    ->where('current_occupancy', 0)
                    ->where('service_locked', false)
                    ->update(['current_service_id' => (int) $attributes['current_service_id']]);
            }
        });
    }

    public function syncMentor(Mentor $mentor): int
    {
        return 0;
    }

    public function syncAllMentors(): int
    {
        return 0;
    }

    public function weekdayLabels(): array
    {
        return self::WEEKDAY_LABELS;
    }

    private function directRulesQuery(Mentor $mentor): Builder|HasMany
    {
        $query = $mentor->availabilityRules();

        return $this->applyDirectRuleConstraints($query);
    }

    private function directSlotsQuery(Mentor $mentor): Builder|HasMany
    {
        return $mentor->availabilitySlots()
            ->where('session_type', '1on1')
            ->where('max_participants', 1);
    }

    private function applyDirectRuleConstraints(Builder|HasMany $query): Builder|HasMany
    {
        return $query
            ->where('session_type', '1on1')
            ->where('max_participants', 1);
    }

    private function deleteFutureUnbookedSlotsForRuleIds(array $ruleIds): void
    {
        if ($ruleIds === []) {
            return;
        }

        MentorAvailabilitySlot::query()
            ->whereIn('availability_rule_id', $ruleIds)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->whereDoesntHave('bookings', fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']))
            ->delete();
    }

    private function deleteFutureUnbookedDirectSlots(Mentor $mentor): void
    {
        $mentor->availabilitySlots()
            ->where('session_type', '1on1')
            ->where('max_participants', 1)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->whereDoesntHave('bookings', fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']))
            ->delete();
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function timeToIndex(string $time): int
    {
        if ($time === '') {
            return 0;
        }

        [$hours, $minutes] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return max(0, min(48, ($hours * 2) + (int) floor($minutes / 30)));
    }

    private function normalizeSlotsPayload(array $day): array
    {
        $slots = collect($day['slots'] ?? [])
            ->filter(fn ($slot) => is_array($slot))
            ->map(fn (array $slot) => [
                'slot_id' => isset($slot['slot_id']) ? (int) $slot['slot_id'] : null,
                'start_time' => (string) ($slot['start_time'] ?? ''),
                'end_time' => (string) ($slot['end_time'] ?? ''),
                'service_config_id' => isset($slot['service_config_id']) ? (int) $slot['service_config_id'] : null,
                'is_booked' => !empty($slot['is_booked']),
                'booking_count' => isset($slot['booking_count']) ? (int) $slot['booking_count'] : 0,
            ])
            ->filter(fn (array $slot) => $slot['start_time'] !== '' || $slot['end_time'] !== '')
            ->values()
            ->all();

        if ($slots !== []) {
            return $slots;
        }

        $startTime = (string) ($day['start_time'] ?? '');
        $endTime = (string) ($day['end_time'] ?? '');

        if ($startTime === '' && $endTime === '') {
            return [];
        }

        return [[
            'slot_id' => isset($day['slot_id']) ? (int) $day['slot_id'] : null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'service_config_id' => isset($day['service_config_id']) ? (int) $day['service_config_id'] : null,
            'is_booked' => !empty($day['is_booked']),
            'booking_count' => isset($day['booking_count']) ? (int) $day['booking_count'] : 0,
        ]];
    }

    private function dayOfWeekKey(Carbon $date): string
    {
        return strtolower(substr($date->format('D'), 0, 3));
    }

    private function formatTimeRange(string $startTime, string $endTime): string
    {
        return $this->formatTimeLabel($startTime).' - '.$this->formatTimeLabel($endTime);
    }

    private function formatTimeLabel(string $time): string
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $time)->format('g:i A');
            } catch (\Throwable) {
                continue;
            }
        }

        return $time;
    }

    private function formatMinutesAsHours(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 hrs';
        }

        $hours = $minutes / 60;

        return fmod($hours, 1.0) === 0.0
            ? (int) $hours.' hrs'
            : number_format($hours, 1).' hrs';
    }

    private function formatEffectiveRange(?string $effectiveFrom, ?string $effectiveUntil): string
    {
        if ($effectiveFrom && $effectiveUntil) {
            return Carbon::parse($effectiveFrom)->format('M j, Y').' to '.Carbon::parse($effectiveUntil)->format('M j, Y');
        }

        if ($effectiveFrom) {
            return 'Starts '.Carbon::parse($effectiveFrom)->format('M j, Y');
        }

        if ($effectiveUntil) {
            return 'Available until '.Carbon::parse($effectiveUntil)->format('M j, Y');
        }

        return 'Ongoing each week until you change it';
    }

    private function formatSlotLabel(MentorAvailabilitySlot $slot): string
    {
        $timezone = $slot->timezone ?: config('app.timezone', 'UTC');
        $startsAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time, $timezone);

        return $startsAt->format('D, M j').' at '.$startsAt->format('g:i A').' '.$startsAt->format('T');
    }

    private function officeHoursNote(string $serviceName, int $occupancy, bool $serviceLocked): string
    {
        if ($occupancy <= 1 && !$serviceLocked) {
            return "This week's office hours are currently set as {$serviceName}. If no one else joins by the cutoff, the first student may request another eligible service.";
        }

        return "This week's office hours are currently set as {$serviceName}. Because multiple students are booked, the session will stay focused on this designated service.";
    }

    private function formatOfficeHoursSummary(string $dayOfWeek, string $startTime, string $timezone, string $frequency): string
    {
        $dayLabel = self::WEEKDAY_LABELS[$dayOfWeek] ?? ucfirst($dayOfWeek);
        $frequencyLabel = $frequency === 'biweekly' ? 'Every other week' : 'Every week';

        return $frequencyLabel.' on '.$dayLabel.' at '.$this->formatTimeLabel($startTime).' '.$this->timezoneAbbreviation($timezone);
    }

    private function timezoneAbbreviation(string $timezone): string
    {
        try {
            return Carbon::now($timezone)->format('T');
        } catch (\Throwable) {
            return $timezone;
        }
    }

    private function programLabel(?string $programType): ?string
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
            'other', null, '' => null,
            default => str_replace('_', ' ', ucfirst((string) $programType)),
        };
    }
}
