<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Str;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingPageService
{
    public function getSelectedMentor(int $mentorId): Mentor
    {
        return Mentor::query()
            ->with([
                'user:id,name',
                'university:id,name,display_name',
                'rating:id,mentor_id,avg_stars',
                'services' => fn($query) => $query
                    ->where('services_config.is_active', true)
                    ->wherePivot('is_active', true)
                    ->orderBy('mentor_services.sort_order')
                    ->orderBy('services_config.sort_order'),
            ])
            ->where('status', 'active')
            ->whereKey($mentorId)
            ->firstOrFail();
    }

    public function buildBookingPageData(Mentor $mentor): array
    {
        $services = $mentor->services
            ->map(fn(ServiceConfig $service) => $this->transformService($service))
            ->values()
            ->all();

        $school = $mentor->grad_school_display
            ?: $mentor->university?->display_name
            ?: $mentor->university?->name
            ?: 'School not listed';

        $mentorName = $mentor->user?->name ?? 'Mentor';
        $programLabel = $this->programLabel($mentor->program_type);
        $firstOfficeHoursService = collect($services)->firstWhere('isOfficeHours', true);
        $fallbackService = collect($services)->firstWhere('isOfficeHours', false);

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
            'services' => $services,
            'officeHours' => [
                'mentorName' => $mentorName,
                'mentorMeta' => trim($programLabel.' • '.$school, ' •'),
                'weeklyService' => $fallbackService['name'] ?? ($firstOfficeHoursService['name'] ?? 'Office Hours'),
                'recurringTime' => $mentor->office_hours_schedule ?: 'Schedule coming soon',
                'spotsFilled' => 0,
                'maxSpots' => 3,
                'meetingType' => 'Small Group Office Hours',
                'soloFallbackAllowed' => true,
            ],
            'selectedServiceId' => $services[0]['id'] ?? null,
        ];
    }

    private function transformService(ServiceConfig $service): array
    {
        $prices = [];

        if ($service->price_1on1 !== null) {
            $prices[1] = $this->formatPriceInfo((float) $service->price_1on1, 1);
        }

        if ($service->price_1on3_per_person !== null) {
            $prices[3] = $this->formatPriceInfo((float) $service->price_1on3_per_person, 3);
        }

        if ($service->price_1on5_per_person !== null) {
            $prices[5] = $this->formatPriceInfo((float) $service->price_1on5_per_person, 5);
        }

        if ($service->is_office_hours) {
            $prices = [
                1 => [
                    'label' => '1 credit',
                    'total' => '1 credit',
                    'amount' => 0,
                ],
            ];
        }

        $allowedSizes = array_map('intval', array_keys($prices));

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
            'program-insights' => 'Get firsthand insight into programs, culture, and what the path is really like.',
            'interview-prep' => 'Practice interviews, sharpen answers, and get direct feedback before the real thing.',
            'application-review' => 'Receive detailed feedback on essays, resumes, and application materials.',
            'gap-year-planning' => 'Plan a purposeful gap year that strengthens your next application cycle.',
            'office-hours' => 'Office Hours are subscription-based and booked using credits at a recurring weekly time.',
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
            ->map(fn(string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
