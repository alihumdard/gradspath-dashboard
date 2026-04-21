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
            'office_hours.frequency' => ['nullable', 'in:weekly,biweekly'],
        ];

        $validator = Validator::make($request->all(), $rules);

        $mentorServiceIds = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->pluck('services_config.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingBookedSlots = $mentor->availabilitySlots()
            ->withCount([
                'bookings as active_bookings_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->where('session_type', '1on1')
            ->where('max_participants', 1)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->get()
            ->filter(fn ($slot) => (int) ($slot->active_bookings_count ?? 0) > 0)
            ->groupBy(fn ($slot) => $slot->slot_date->toDateString());

        $validator->after(function ($validator) use ($request, $mentor, $mentorServiceIds, $existingBookedSlots) {
            $submittedDateValues = collect((array) $request->input('date_slots', []))
                ->map(fn ($dateSlot) => (string) ($dateSlot['date'] ?? ''))
                ->filter()
                ->values();

            $officeHours = (array) $request->input('office_hours', []);
            $officeHoursEnabled = filter_var($officeHours['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $scheduleTimezone = (string) ($request->input('timezone') ?: config('app.timezone', 'UTC'));

            try {
                $scheduleNow = now($scheduleTimezone);
            } catch (\Throwable) {
                $scheduleTimezone = (string) config('app.timezone', 'UTC');
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

                usort($normalizedSlots, fn (array $left, array $right) => strcmp($left['start_time'], $right['start_time']));

                $previousEnd = null;

                foreach ($normalizedSlots as $index => $slot) {
                    if ($previousEnd !== null && $slot['start_time'] < $previousEnd) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.start_time", "{$dateLabel} time blocks cannot overlap.");
                    }

                    if (!empty($slot['is_booked'])) {
                        $previousEnd = $slot['end_time'];
                        continue;
                    }

                    if (!$slot['service_config_id']) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} requires a service for every block.");
                    } elseif (!in_array($slot['service_config_id'], $mentorServiceIds, true)) {
                        $validator->errors()->add("date_slots.{$dateIndex}.slots.{$index}.service_config_id", "{$dateLabel} must use one of your active mentor services.");
                    }

                    if ($dateValue !== '') {
                        try {
                            $slotStartsAt = Carbon::parse($dateValue.' '.$slot['start_time'], $scheduleTimezone);

                            if ($slotStartsAt->lte($scheduleNow)) {
                                $validator->errors()->add(
                                    "date_slots.{$dateIndex}.slots.{$index}.start_time",
                                    "{$dateLabel} time blocks must start in the future."
                                );
                            }
                        } catch (\Throwable) {
                            // Let the existing date/time validation handle malformed values.
                        }
                    }

                    $previousEnd = $slot['end_time'];
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

            if ($timezone === '') {
                $validator->errors()->add('office_hours.timezone', 'Choose the office-hours timezone.');
            }

            if ($frequency === '') {
                $validator->errors()->add('office_hours.frequency', 'Choose whether office hours repeat weekly or biweekly.');
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

    private function timezoneOptions(): array
    {
        return [
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time',
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
            'Europe/London' => 'London',
            'Asia/Karachi' => 'Karachi',
            'UTC' => 'UTC',
        ];
    }

    private function pageData(Mentor $mentor): array
    {
        $formData = $this->availability->formData($mentor);
        $insights = $this->availability->insights($mentor);
        $timezoneOptions = $this->timezoneOptions();
        $serviceOptions = $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get(['services_config.id', 'services_config.service_name'])
            ->map(fn (ServiceConfig $service) => [
                'value' => (int) $service->id,
                'label' => $service->service_name,
            ])
            ->values()
            ->all();
        $officeHoursConfig = $this->availability->officeHoursConfig($mentor);
        $officeHoursPreview = $this->availability->officeHoursPreview($mentor, $officeHoursConfig);
        $schedulerPayload = $this->availability->schedulerPayload($formData, $insights, $timezoneOptions, $serviceOptions);
        $schedulerPayload['office_hours'] = [
            'config' => $officeHoursConfig,
            'preview' => $officeHoursPreview,
        ];

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
