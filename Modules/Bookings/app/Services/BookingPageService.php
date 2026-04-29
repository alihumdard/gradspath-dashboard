<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Support\TimezoneOptions;

class BookingPageService
{
    public function __construct(private readonly BookingAvailabilityService $availability) {}

    public function getSelectedMentor(int $mentorId): Mentor
    {
        return Mentor::query()
            ->with([
                'user:id,name',
                'university:id,name,display_name',
                'rating:id,mentor_id,avg_stars',
                'services' => fn ($query) => $query
                    ->where('services_config.is_active', true)
                    ->wherePivot('is_active', true)
                    ->orderBy('mentor_services.sort_order')
                    ->orderBy('services_config.sort_order'),
            ])
            ->where('status', 'active')
            ->whereKey($mentorId)
            ->firstOrFail();
    }

    public function buildBookingPageData(Mentor $mentor, ?User $viewer = null, array $options = []): array
    {
        $portal = (string) ($options['portal'] ?? 'student');
        $allowOfficeHours = (bool) ($options['allow_office_hours'] ?? ($portal === 'student'));
        $maxMeetingSize = (int) ($options['max_meeting_size'] ?? 5);

        $school = $mentor->grad_school_display
            ?: $mentor->university?->display_name
            ?: $mentor->university?->name
            ?: 'School not listed';

        $mentorName = $mentor->user?->name ?? 'Mentor';
        $programLabel = $this->programLabel($mentor->program_type);
        $viewerTimezone = TimezoneOptions::preferredFor($viewer?->loadMissing('setting'));
        $mentorHasOfficeHoursService = $allowOfficeHours && $this->mentorHasOfficeHoursService($mentor);
        $activeOfficeHoursSchedule = $mentorHasOfficeHoursService
            && $mentor->officeHourSchedules()->where('is_active', true)->exists();
        $officeHours = $activeOfficeHoursSchedule
            ? ($this->availability->nextOfficeHourSessionForMentor($mentor, $viewerTimezone) ?? [
                'sessionId' => null,
                'mentorName' => $mentorName,
                'mentorMeta' => trim($programLabel.' • '.$school, ' •'),
                'weeklyService' => 'Office Hours',
                'recurringTime' => $mentor->office_hours_schedule ?: 'Schedule coming soon',
                'meetingType' => 'Small Group Office Hours',
                'spotsFilled' => 0,
                'maxSpots' => 3,
                'remainingSpots' => 3,
                'isFull' => false,
                'isBookable' => false,
                'availabilityText' => 'Availability updates soon',
                'sessionDate' => null,
                'sessionTime' => null,
                'rotation' => null,
                'serviceLocked' => false,
                'note' => 'This mentor has not published an upcoming office-hours session yet.',
            ])
            : null;
        $servicesForBooking = $mentor->services;
        $hasOfficeHoursSchedule = $activeOfficeHoursSchedule || (bool) ($officeHours['sessionId'] ?? false);

        if (!$hasOfficeHoursSchedule) {
            $servicesForBooking = $servicesForBooking
                ->reject(fn (ServiceConfig $service) => (bool) $service->is_office_hours)
                ->values();
        }

        if ($hasOfficeHoursSchedule && ! $servicesForBooking->contains(fn (ServiceConfig $service) => (bool) $service->is_office_hours)) {
            $officeHoursService = ServiceConfig::query()
                ->where('is_active', true)
                ->where('is_office_hours', true)
                ->orderBy('sort_order')
                ->first();

            if ($officeHoursService) {
                $servicesForBooking = $servicesForBooking->push($officeHoursService);
            }
        }

        $services = $servicesForBooking
            ->map(fn (ServiceConfig $service) => $this->transformService($service, $maxMeetingSize, $allowOfficeHours))
            ->filter()
            ->values()
            ->all();
        $selectedServiceId = $this->selectedServiceId($services, (string) ($options['selected_service'] ?? ''));

        return [
            'mentor' => [
                'id' => $mentor->id,
                'name' => $mentorName,
                'initials' => $this->initials($mentorName),
                'meta' => trim($programLabel.' • '.$school, ' •'),
                'description' => $mentor->bio
                    ?: $mentor->description
                    ?: 'This mentor is available to support strategy, applications, and next steps.',
                'rating' => $mentor->rating?->avg_stars
                    ? number_format((float) $mentor->rating->avg_stars, 1)
                    : 'New',
            ],
            'student' => [
                'name' => $viewer?->name,
                'email' => $viewer?->email,
            ],
            'viewer' => [
                'name' => $viewer?->name,
                'email' => $viewer?->email,
                'portal' => $portal,
                'timezone' => $viewerTimezone,
                'hasSavedTimezone' => filled($viewer?->setting?->timezone),
            ],
            'services' => $services,
            'officeHours' => $officeHours,
            'selectedServiceId' => $selectedServiceId,
            'availabilityRoutes' => [
                'months' => route("{$portal}.bookings.availability.months"),
                'days' => route("{$portal}.bookings.availability.days"),
                'times' => route("{$portal}.bookings.availability.times"),
            ],
            'bookingSubmitUrl' => route("{$portal}.bookings.store"),
            'bookingCheckoutUrl' => route("{$portal}.bookings.checkout.store"),
            'creditBalanceUrl' => $portal === 'student' ? route('student.credits.balance') : null,
            'dashboardUrl' => route("{$portal}.dashboard"),
            'officeHoursDirectoryUrl' => $portal === 'student'
                ? route('student.office-hours')
                : route('mentor.office-hours'),
            'timezoneAutoSaveUrl' => route('settings.timezone.store'),
        ];
    }

    private function selectedServiceId(array $services, string $requestedService): ?string
    {
        if ($requestedService !== '') {
            $normalized = str_replace('-', '_', Str::lower($requestedService));

            if (in_array($normalized, ['office_hours', 'officehours'], true)) {
                $officeHours = collect($services)->first(fn (array $service) => (bool) ($service['isOfficeHours'] ?? false));

                if ($officeHours) {
                    return $officeHours['id'];
                }
            }

            $requested = collect($services)->first(function (array $service) use ($requestedService, $normalized) {
                return (string) $service['id'] === $requestedService
                    || str_replace('-', '_', Str::lower((string) $service['id'])) === $normalized;
            });

            if ($requested) {
                return $requested['id'];
            }
        }

        return $services[0]['id'] ?? null;
    }

    private function mentorHasOfficeHoursService(Mentor $mentor): bool
    {
        return $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', true)
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function transformService(ServiceConfig $service, int $maxMeetingSize = 5, bool $allowOfficeHours = true): ?array
    {
        if (!$allowOfficeHours && (bool) $service->is_office_hours) {
            return null;
        }

        $prices = [];

        if ($service->price_1on1 !== null && $maxMeetingSize >= 1) {
            $prices[1] = $this->formatPriceInfo((float) $service->price_1on1, 1);
        }

        if ($service->price_1on3_per_person !== null && $maxMeetingSize >= 3) {
            $prices[3] = $this->formatPriceInfo((float) $service->price_1on3_per_person, 3);
        }

        if ($service->price_1on5_per_person !== null && $maxMeetingSize >= 5) {
            $prices[5] = $this->formatPriceInfo((float) $service->price_1on5_per_person, 5);
        }

        if ($service->is_office_hours && $allowOfficeHours) {
            $prices = [
                1 => [
                    'label' => '1 credit',
                    'total' => '1 credit',
                    'amount' => 0,
                ],
            ];
        }

        $allowedSizes = array_map('intval', array_keys($prices));

        if ($allowedSizes === []) {
            return null;
        }

        return [
            'id' => $service->service_slug ?: Str::slug($service->service_name),
            'serviceConfigId' => $service->id,
            'name' => $service->service_name,
            'duration' => ((int) $service->duration_minutes).' min',
            'desc' => $this->serviceDescription($service),
            'prices' => $prices,
            'allowedSizes' => $allowedSizes,
            'defaultSize' => $allowedSizes[0] ?? 1,
            'isOfficeHours' => (bool) $service->is_office_hours,
        ];
    }

    private function formatPriceInfo(float $pricePerPerson, int $size): array
    {
        if ($pricePerPerson <= 0) {
            return [
                'label' => 'Free',
                'total' => 'Free',
                'amount' => 0,
            ];
        }

        if ($size === 1) {
            $formatted = '$'.number_format($pricePerPerson, 2);

            return [
                'label' => $formatted,
                'total' => $formatted,
                'amount' => $pricePerPerson,
            ];
        }

        $total = $pricePerPerson * $size;

        return [
            'label' => '$'.number_format($total, 2).' total • $'.number_format($pricePerPerson, 2).' each',
            'total' => '$'.number_format($total, 2).' total',
            'amount' => $total,
        ];
    }

    private function serviceDescription(ServiceConfig $service): string
    {
        return match ($service->service_slug) {
            'free-consultation' => 'Meet the mentor, assess fit, and align your goals in a focused introductory session.',
            'tutoring' => 'Targeted academic or test-prep support shaped around this mentor\'s expertise.',
            'program-insights', 'program_insights' => 'Get firsthand insight into programs, culture, and what the path is really like.',
            'interview-prep', 'interview_prep' => 'Practice interviews, sharpen answers, and get direct feedback before the real thing.',
            'application-review', 'application_review' => 'Receive detailed feedback on essays, resumes, and application materials.',
            'gap-year-planning', 'gap_year_planning' => 'Plan a purposeful gap year that strengthens your next application cycle.',
            'office-hours', 'office_hours' => 'Office Hours are subscription-based and booked using credits at a recurring weekly time.',
            default => 'Book this mentor for focused guidance tailored to your goals and next steps.',
        };
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
            'other', null, '' => 'Mentor',
            default => ucfirst(str_replace('_', ' ', (string) $programType)),
        };
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
