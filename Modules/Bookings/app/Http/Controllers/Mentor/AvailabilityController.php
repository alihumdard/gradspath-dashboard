<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\Bookings\app\Services\MentorAvailabilityManagerService;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Support\TimezoneOptions;

class AvailabilityController extends Controller
{
    public function __construct(private readonly MentorAvailabilityManagerService $availability) {}

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
            'office_hours' => array_merge((array) $request->input('office_hours', []), [
                'timezone' => (string) $request->input('timezone', TimezoneOptions::fallback()),
            ]),
        ]);

        $rules = [
            'timezone' => ['required', 'string', 'max:80'],
            'date_slots' => ['nullable', 'array'],
            'date_slots.*.date' => ['required', 'date'],
            'date_slots.*.enabled' => ['nullable', 'boolean'],
            'date_slots.*.slots' => ['nullable', 'array'],
            'date_slots.*.slots.*.start_time' => ['nullable', 'date_format:H:i'],
            'date_slots.*.slots.*.end_time' => ['nullable', 'date_format:H:i'],
            'date_slots.*.slots.*.service_config_id' => ['nullable', 'integer'],
            'office_hours' => ['nullable', 'array'],
            'office_hours.enabled' => ['nullable', 'boolean'],
            'office_hours.service_config_id' => ['nullable', 'integer'],
            'office_hours.day_of_week' => ['nullable', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'office_hours.start_time' => ['nullable', 'date_format:H:i'],
            'office_hours.timezone' => ['nullable', 'string', 'max:80'],
            'office_hours.frequency' => ['nullable', 'in:weekly'],
        ];

        $validator = Validator::make($request->all(), $rules);

        $mentorServiceIds = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->pluck('services_config.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $mentorServiceDurations = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->pluck('services_config.duration_minutes', 'services_config.id')
            ->mapWithKeys(fn ($duration, $id) => [(int) $id => max((int) $duration, 1)])
            ->all();

        $existingBookedSlots = $mentor->availabilitySlots()
            ->withCount([
                'bookings as active_bookings_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->where('session_type', '1on1')
            ->where('max_participants', 1)
            ->where(function ($query) {
                $query->where('starts_at_utc', '>', now('UTC'))
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery
                            ->whereNull('starts_at_utc')
                            ->whereDate('slot_date', '>=', now('UTC')->toDateString());
                    });
            })
            ->get()
            ->filter(fn ($slot) => (int) ($slot->active_bookings_count ?? 0) > 0)
            ->groupBy(fn ($slot) => $slot->slot_date->toDateString());

        $validator->after(function ($validator) use ($request, $mentor, $mentorServiceIds, $mentorServiceDurations, $existingBookedSlots) {
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

                    if (
                        $submittedStartTime !== substr((string) $bookedSlot->start_time, 0, 5)
                        || $submittedEndTime !== substr((string) $bookedSlot->end_time, 0, 5)
                        || $submittedServiceId !== (int) $bookedSlot->service_config_id
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

                    if (!$slot['service_config_id']) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} requires a service for every block.");
                    } elseif (!in_array($slot['service_config_id'], $mentorServiceIds, true)) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} must use one of your active mentor services.");
                    } elseif (($mentorServiceDurations[$slot['service_config_id']] ?? null) === null) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} must use a service with a valid duration.");
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
                    'message' => 'Please fix the highlighted availability settings before saving.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

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
        $serviceOptions = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get(['services_config.id', 'services_config.service_name', 'services_config.duration_minutes'])
            ->map(fn (ServiceConfig $service) => [
                'value' => (int) $service->id,
                'label' => $service->service_name,
                'duration_minutes' => max((int) $service->duration_minutes, 1),
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
        $schedulerPayload['has_saved_timezone'] = filled(Auth::user()?->setting?->timezone);
        $schedulerPayload['timezone_autosave_url'] = route('settings.timezone.store');

        return [
            'availabilityData' => $formData,
            'availabilityInsights' => $insights,
            'timezoneOptions' => $timezoneOptions,
            'schedulerPayload' => $schedulerPayload,
            'officeHoursConfig' => $officeHoursConfig,
            'officeHoursPreview' => $officeHoursPreview,
        ];
    }
}
