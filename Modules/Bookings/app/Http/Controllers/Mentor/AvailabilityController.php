<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Bookings\app\Services\MentorAvailabilityManagerService;
use Modules\Bookings\app\Services\ZoomService;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Support\TimezoneOptions;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly MentorAvailabilityManagerService $availability,
        private readonly ZoomService $zoom,
    ) {}

    public function index(): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();

        return view('bookings::mentor.availability', $this->pageData($mentor));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();
        $dateSlots = json_decode((string) $request->input('date_slots_payload', '[]'), true);

        if (!is_array($dateSlots)) {
            $dateSlots = [];
        }

        $request->merge([
            'date_slots' => array_values($dateSlots),
            'effective_from' => null,
            'effective_until' => null,
            'timezone' => TimezoneOptions::fallback(),
            'office_hours' => array_merge((array) $request->input('office_hours', []), [
                'timezone' => TimezoneOptions::fallback(),
            ]),
        ]);

        $rules = [
            'timezone' => ['required', 'string', 'max:80', Rule::in(TimezoneOptions::values())],
            'date_slots' => ['nullable', 'array'],
            'date_slots.*.date' => ['required', 'date'],
            'date_slots.*.enabled' => ['nullable', 'boolean'],
            'date_slots.*.slots' => ['nullable', 'array'],
            'date_slots.*.slots.*.start_time' => ['nullable', 'date_format:H:i'],
            'date_slots.*.slots.*.end_time' => ['nullable', 'date_format:H:i'],
            'date_slots.*.slots.*.service_config_id' => ['nullable', 'integer'],
            'date_slots.*.slots.*.session_type' => ['nullable', 'in:1on1,1on3,1on5'],
            'office_hours' => ['nullable', 'array'],
            'office_hours.enabled' => ['nullable', 'boolean'],
            'office_hours.service_config_id' => ['nullable', 'integer'],
            'office_hours.day_of_week' => ['nullable', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'office_hours.start_time' => ['nullable', 'date_format:H:i'],
            'office_hours.timezone' => ['nullable', 'string', 'max:80', Rule::in(TimezoneOptions::values())],
            'office_hours.frequency' => ['nullable', 'in:weekly'],
            'service_config_ids_present' => ['nullable', 'boolean'],
            'service_config_ids' => ['nullable', 'array'],
            'service_config_ids.*' => [
                'integer',
                Rule::exists('services_config', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        $shouldSyncServices = $request->boolean('service_config_ids_present');
        $submittedServiceIds = collect((array) $request->input('service_config_ids', []))
            ->map(fn (mixed $id) => (int) $id)
            ->unique()
            ->values();
        $activeSubmittedServiceIds = ServiceConfig::query()
            ->whereIn('id', $submittedServiceIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $activeMentorServiceQuery = $shouldSyncServices
            ? ServiceConfig::query()->whereIn('id', $activeSubmittedServiceIds)
            : $mentor->services()->where('services_config.is_active', true);

        $mentorServiceIds = (clone $activeMentorServiceQuery)
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->pluck('services_config.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $mentorServiceDurations = (clone $activeMentorServiceQuery)
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->pluck('services_config.duration_minutes', 'services_config.id')
            ->mapWithKeys(fn ($duration, $id) => [(int) $id => max((int) $duration, 1)])
            ->all();
        $mentorServiceMeetingSizes = (clone $activeMentorServiceQuery)
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->get([
                'services_config.id',
                'services_config.price_1on1',
                'services_config.price_1on3_per_person',
                'services_config.price_1on5_per_person',
            ])
            ->mapWithKeys(fn (ServiceConfig $service) => [
                (int) $service->id => $this->allowedSessionTypesForService($service),
            ])
            ->all();
        $submittedSlotIds = collect((array) $request->input('date_slots', []))
            ->flatMap(fn ($dateSlot) => collect((array) ($dateSlot['slots'] ?? []))
                ->map(fn ($slot) => (int) ($slot['slot_id'] ?? 0)))
            ->filter()
            ->unique()
            ->values();
        $existingSubmittedSlots = $submittedSlotIds->isEmpty()
            ? collect()
            : $mentor->availabilitySlots()
                ->whereIn('id', $submittedSlotIds)
                ->get()
                ->keyBy('id');

        $existingBookedSlots = $mentor->availabilitySlots()
            ->withCount([
                'bookings as active_bookings_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->where(function ($query) {
                $query->where('starts_at_utc', '>', now('UTC'))
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery
                            ->whereNull('starts_at_utc')
                            ->whereDate('slot_date', '>=', now(TimezoneOptions::fallback())->toDateString());
                    });
            })
            ->get()
            ->filter(fn ($slot) => (int) ($slot->active_bookings_count ?? 0) > 0)
            ->groupBy(fn ($slot) => $slot->slot_date->toDateString());

        $validator->after(function ($validator) use ($request, $mentor, $mentorServiceIds, $mentorServiceDurations, $mentorServiceMeetingSizes, $existingBookedSlots, $existingSubmittedSlots) {
            $submittedDateValues = collect((array) $request->input('date_slots', []))
                ->map(fn ($dateSlot) => (string) ($dateSlot['date'] ?? ''))
                ->filter()
                ->values();

            $officeHours = (array) $request->input('office_hours', []);
            $officeHoursEnabled = filter_var($officeHours['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $scheduleTimezone = (string) ($request->input('timezone') ?: TimezoneOptions::fallback());

            try {
                $scheduleNow = now($scheduleTimezone);
            } catch (\Throwable) {
                $scheduleTimezone = TimezoneOptions::fallback();
                $scheduleNow = now($scheduleTimezone);
            }

            foreach ((array) $request->input('date_slots', []) as $dateIndex => $dateSlot) {
                $dateValue = (string) ($dateSlot['date'] ?? '');
                $dateLabel = $dateValue !== '' ? Carbon::parse($dateValue)->format('l, F j, Y') : 'This date';
                $enabled = filter_var($dateSlot['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $submittedSlots = collect($dateSlot['slots'] ?? [])
                    ->filter(fn ($slot) => is_array($slot))
                    ->values();
                $bookedSlotsForDate = collect($existingBookedSlots->get($dateValue, collect()));

                if ($dateValue !== '' && $dateValue < $scheduleNow->toDateString()) {
                    $validator->errors()->add("date_slots.{$dateIndex}.date", "{$dateLabel} has passed and cannot accept new availability.");
                    continue;
                }

                if ($bookedSlotsForDate->isNotEmpty() && !$enabled) {
                    $validator->errors()->add("date_slots.{$dateIndex}.enabled", "{$dateLabel} has booked slots and cannot be cleared.");
                    continue;
                }

                foreach ($bookedSlotsForDate as $bookedSlot) {
                    $matchingSubmittedSlot = $submittedSlots->first(fn ($slot) => (int) ($slot['slot_id'] ?? 0) === (int) $bookedSlot->id);

                    if (!$matchingSubmittedSlot) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots", "{$dateLabel} contains booked slots that must stay locked.");
                        continue;
                    }

                    $submittedStartTime = (string) ($matchingSubmittedSlot['start_time'] ?? '');
                    $submittedEndTime = (string) ($matchingSubmittedSlot['end_time'] ?? '');
                    $submittedServiceId = isset($matchingSubmittedSlot['service_config_id']) ? (int) $matchingSubmittedSlot['service_config_id'] : null;
                    $submittedSessionType = $this->normalizeSessionType((string) ($matchingSubmittedSlot['session_type'] ?? '1on1'));

                    if (
                        $submittedStartTime !== substr((string) $bookedSlot->start_time, 0, 5)
                        || $submittedEndTime !== substr((string) $bookedSlot->end_time, 0, 5)
                        || $submittedServiceId !== (int) $bookedSlot->service_config_id
                        || $submittedSessionType !== $this->normalizeSessionType((string) $bookedSlot->session_type)
                    ) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots", "{$dateLabel} has booked slots that cannot be edited.");
                    }
                }

                if (!$enabled) {
                    continue;
                }

                $slots = $submittedSlots;

                $normalizedSlots = [];

                foreach ($slots as $index => $slot) {
                    $startTime = (string) ($slot['start_time'] ?? '');
                    $endTime = (string) ($slot['end_time'] ?? '');

                    if ($startTime === '' && $endTime === '') {
                        continue;
                    }

                    if ($startTime === '' || $endTime === '') {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.start_time", "{$dateLabel} requires both a start and end time for each block.");
                        continue;
                    }

                    if ($startTime >= $endTime) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.end_time", "{$dateLabel} must end after it starts for every block.");
                        continue;
                    }

                    $normalizedSlots[] = [
                        'slot_id' => isset($slot['slot_id']) ? (int) $slot['slot_id'] : null,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'service_config_id' => isset($slot['service_config_id']) ? (int) $slot['service_config_id'] : null,
                        'session_type' => $this->normalizeSessionType((string) ($slot['session_type'] ?? '1on1')),
                        'is_booked' => !empty($slot['is_booked']),
                    ];
                }

                if ($normalizedSlots === []) {
                    $validator->errors()->add("date_slots.{$dateIndex}.slots", "{$dateLabel} requires at least one time block.");
                    continue;
                }

                $normalizedSlots = collect($normalizedSlots)
                    ->map(function (array $slot) use ($dateValue, $scheduleTimezone) {
                        try {
                            $startsAt = Carbon::parse($dateValue.' '.$slot['start_time'], $scheduleTimezone);
                            $endsAt = Carbon::parse($dateValue.' '.$slot['end_time'], $scheduleTimezone);
                        } catch (\Throwable) {
                            $startsAt = null;
                            $endsAt = null;
                        }

                        return $slot + [
                            'starts_at_utc' => $startsAt?->copy()->utc(),
                            'ends_at_utc' => $endsAt?->copy()->utc(),
                        ];
                    })
                    ->sortBy(fn (array $slot) => $slot['starts_at_utc']?->timestamp ?? 0)
                    ->values()
                    ->all();

                $previousEndUtc = null;

                foreach ($normalizedSlots as $index => $slot) {
                    if (
                        $previousEndUtc !== null
                        && $slot['starts_at_utc']
                        && $slot['starts_at_utc']->lt($previousEndUtc)
                    ) {
                        $validator->errors()->add(
                            "date_slots.{$dateIndex}.slots.{$index}.start_time",
                            "{$dateLabel}: this time overlaps another slot. Choose a start time after the previous slot ends, or remove one of the slots."
                        );
                    }

                    if (!empty($slot['is_booked'])) {
                        $previousEndUtc = $slot['ends_at_utc'] ?: $previousEndUtc;
                        continue;
                    }

                    $existingSlot = $slot['slot_id'] ? $existingSubmittedSlots->get($slot['slot_id']) : null;
                    $isUnchangedExistingSlot = $existingSlot
                        && $slot['start_time'] === substr((string) $existingSlot->start_time, 0, 5)
                        && $slot['end_time'] === substr((string) $existingSlot->end_time, 0, 5)
                        && (int) $slot['service_config_id'] === (int) $existingSlot->service_config_id
                        && $slot['session_type'] === $this->normalizeSessionType((string) $existingSlot->session_type);

                    if (
                        !$isUnchangedExistingSlot
                        && $slot['starts_at_utc']
                        && $slot['starts_at_utc']->lte(now('UTC'))
                    ) {
                        $validator->errors()->add(
                            "date_slots.{$dateIndex}.slots.{$index}.start_time",
                            "{$dateLabel} has a new slot that starts in the past. Choose a future start time."
                        );
                    }

                    if (!$slot['service_config_id']) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} requires a service for every block.");
                    } elseif (!in_array($slot['service_config_id'], $mentorServiceIds, true)) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} must use one of your active mentor services.");
                    } elseif (($mentorServiceDurations[$slot['service_config_id']] ?? null) === null) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} must use a service with a valid duration.");
                    } elseif (!in_array($slot['session_type'], $mentorServiceMeetingSizes[$slot['service_config_id']] ?? [], true)) {
                        $validator->errors()->add(
                            "date_slots.{$dateIndex}.slots.{$index}.session_type",
                            "{$dateLabel} uses a meeting size that is not available for the selected service."
                        );
                    }

                    $previousEndUtc = $slot['ends_at_utc'] ?: $previousEndUtc;
                }
            }

            if ($mentorServiceIds === [] && collect($request->input('date_slots', []))
                ->contains(fn ($dateSlot) => filter_var($dateSlot['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                $validator->errors()->add('date_slots', 'Add at least one active mentor service before opening booking availability.');
            }

            foreach ($existingBookedSlots as $dateValue => $bookedSlotsForDate) {
                if ($bookedSlotsForDate->isNotEmpty() && !$submittedDateValues->contains($dateValue)) {
                    $dateLabel = Carbon::parse($dateValue)->format('l, F j, Y');
                    $validator->errors()->add('date_slots', "{$dateLabel} contains booked slots that must stay locked.");
                }
            }

            if ($this->hasEnabledRegularAvailability($request)) {
                if (! $this->zoom->isConfigured()) {
                    $validator->errors()->add('date_slots', 'Zoom booking is not configured right now.');
                } elseif (! $this->zoom->hasConnectedMentor($mentor)) {
                    $validator->errors()->add('date_slots', 'Connect Zoom before publishing student-bookable availability.');
                } else {
                    try {
                        $this->zoom->assertMentorConnectionIsUsable($mentor);
                    } catch (\RuntimeException $exception) {
                        $validator->errors()->add('date_slots', $this->mentorZoomAvailabilityMessage($exception));
                    }
                }
            }

            if (!$officeHoursEnabled) {
                return;
            }

            if ($mentorServiceIds === []) {
                $validator->errors()->add('office_hours.service_config_id', 'Add at least one active mentor service before enabling office hours.');

                return;
            }

            $serviceId = isset($officeHours['service_config_id']) ? (int) $officeHours['service_config_id'] : null;
            $dayOfWeek = (string) ($officeHours['day_of_week'] ?? '');
            $startTime = (string) ($officeHours['start_time'] ?? '');
            $timezone = trim((string) ($officeHours['timezone'] ?? ''));
            $frequency = (string) ($officeHours['frequency'] ?? '');

            if (!$serviceId) {
                $validator->errors()->add('office_hours.service_config_id', 'Choose the weekly focus service for office hours.');
            } elseif (!in_array($serviceId, $mentorServiceIds, true)) {
                $validator->errors()->add('office_hours.service_config_id', 'Office hours must use one of your active mentor services.');
            }

            if ($dayOfWeek === '') {
                $validator->errors()->add('office_hours.day_of_week', 'Choose the recurring day for office hours.');
            }

            if ($startTime === '') {
                $validator->errors()->add('office_hours.start_time', 'Choose the recurring start time for office hours.');
            }

            if ($frequency === '') {
                $validator->errors()->add('office_hours.frequency', 'Choose weekly office hours.');
            } elseif ($frequency !== 'weekly') {
                $validator->errors()->add('office_hours.frequency', 'Office hours must repeat weekly.');
            }

            $activeScheduleCount = OfficeHourSchedule::query()
                ->where('mentor_id', $mentor->id)
                ->where('is_active', true)
                ->count();

            if ($activeScheduleCount > 1) {
                $validator->errors()->add('office_hours', 'Only one active office-hours schedule is supported per mentor.');
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first() ?: 'Please fix the highlighted availability settings before saving.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if ($shouldSyncServices) {
            $serviceIds = collect($data['service_config_ids'] ?? [])
                ->map(fn (mixed $id) => (int) $id)
                ->unique()
                ->values();

            $mentor->services()->sync(
                $serviceIds->mapWithKeys(fn (int $id, int $index) => [
                    $id => ['sort_order' => $index],
                ])->all()
            );

            $officeHoursEnabled = ServiceConfig::query()
                ->whereIn('id', $serviceIds)
                ->where('is_active', true)
                ->where('is_office_hours', true)
                ->exists();

            if (! $officeHoursEnabled) {
                $mentor->officeHourSchedules()
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $mentor->load('services');
        }

        $this->availability->updateWeeklyAvailability($mentor, $data);
        $this->availability->saveOfficeHours($mentor, (array) ($data['office_hours'] ?? []));

        if ($request->expectsJson()) {
            $pageData = $this->pageData($mentor);

            return response()->json([
                'message' => 'Mentor availability updated successfully.',
                'formData' => $pageData['availabilityData'],
                'insights' => $pageData['availabilityInsights'],
                'scheduler' => $pageData['schedulerPayload'],
                'officeHoursConfig' => $pageData['officeHoursConfig'],
                'officeHoursPreview' => $pageData['officeHoursPreview'],
            ]);
        }

        return redirect()
            ->route('mentor.availability.index')
            ->with('success', 'Mentor availability updated successfully.');
    }

    private function pageData(Mentor $mentor): array
    {
        $preferredTimezone = TimezoneOptions::preferredFor(Auth::user()?->loadMissing('setting'));
        $formData = $this->availability->formData($mentor, $preferredTimezone);
        $insights = $this->availability->insights($mentor);
        $timezoneOptions = TimezoneOptions::all();
        $services = ServiceConfig::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('service_name')
            ->get();
        $selectedServiceIds = $mentor->services()
            ->pluck('services_config.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $serviceOptions = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get([
                'services_config.id',
                'services_config.service_name',
                'services_config.duration_minutes',
                'services_config.price_1on1',
                'services_config.price_1on3_per_person',
                'services_config.price_1on5_per_person',
            ])
            ->map(fn (ServiceConfig $service) => [
                'value' => (int) $service->id,
                'label' => $service->service_name,
                'duration_minutes' => max((int) $service->duration_minutes, 1),
                'allowed_sizes' => $this->allowedSessionTypesForService($service),
            ])
            ->values()
            ->all();
        $officeHoursConfig = $this->availability->officeHoursConfig($mentor, $preferredTimezone);
        $officeHoursPreview = $this->availability->officeHoursPreview($mentor, $officeHoursConfig);
        $schedulerPayload = $this->availability->schedulerPayload($formData, $insights, $timezoneOptions, $serviceOptions);
        $schedulerPayload['office_hours'] = [
            'config' => $officeHoursConfig,
            'preview' => $officeHoursPreview,
        ];
        $schedulerPayload['zoom'] = $this->mentorZoomPayload();
        $schedulerPayload['has_saved_timezone'] = filled(Auth::user()?->setting?->timezone);
        $schedulerPayload['timezone_autosave_url'] = route('settings.timezone.store');

        return [
            'availabilityData' => $formData,
            'availabilityInsights' => $insights,
            'timezoneOptions' => $timezoneOptions,
            'schedulerPayload' => $schedulerPayload,
            'officeHoursConfig' => $officeHoursConfig,
            'officeHoursPreview' => $officeHoursPreview,
            'services' => $services,
            'selectedServiceIds' => $selectedServiceIds,
        ];
    }

    private function allowedSessionTypesForService(ServiceConfig $service): array
    {
        $types = [];

        if ($service->price_1on1 !== null) {
            $types[] = '1on1';
        }

        if ($service->price_1on3_per_person !== null) {
            $types[] = '1on3';
        }

        if ($service->price_1on5_per_person !== null) {
            $types[] = '1on5';
        }

        return $types !== [] ? $types : ['1on1'];
    }

    private function normalizeSessionType(string $sessionType): string
    {
        return in_array($sessionType, ['1on1', '1on3', '1on5'], true) ? $sessionType : '1on1';
    }

    private function hasEnabledRegularAvailability(Request $request): bool
    {
        return collect((array) $request->input('date_slots', []))
            ->contains(function ($dateSlot) {
                if (! is_array($dateSlot) || ! filter_var($dateSlot['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    return false;
                }

                return collect((array) ($dateSlot['slots'] ?? []))
                    ->filter(fn ($slot) => is_array($slot))
                    ->contains(function (array $slot) {
                        return trim((string) ($slot['start_time'] ?? '')) !== ''
                            || trim((string) ($slot['end_time'] ?? '')) !== ''
                            || ! empty($slot['slot_id'])
                            || ! empty($slot['service_config_id']);
                    });
            });
    }

    private function mentorZoomAvailabilityMessage(\RuntimeException $exception): string
    {
        $message = $exception->getMessage();

        if (
            str_contains($message, 'reconnect Zoom')
            || str_contains($message, 'revoked')
            || str_contains($message, 'expired')
            || str_contains($message, 'missing')
        ) {
            return 'Reconnect Zoom before publishing student-bookable availability.';
        }

        if (str_contains($message, 'not connected') || str_contains($message, 'connected Zoom')) {
            return 'Connect Zoom before publishing student-bookable availability.';
        }

        return 'Zoom connection could not be verified right now. Please try again shortly before publishing student-bookable availability.';
    }

    private function mentorZoomPayload(): array
    {
        $user = Auth::user();
        $status = $user ? $this->zoom->connectionStatusForUser($user) : 'not_connected';
        $isBookable = $this->zoom->isConfigured() && $status === 'connected';

        return [
            'status' => $status,
            'isBookable' => $isBookable,
            'message' => match ($status) {
                'error' => 'Reconnect Zoom before adding student-bookable availability.',
                'connected' => null,
                default => 'Connect Zoom before adding student-bookable availability.',
            },
        ];
    }
}
