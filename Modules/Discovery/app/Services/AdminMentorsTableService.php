<?php

namespace Modules\Discovery\app\Services;

use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class AdminMentorsTableService
{
    private const PROGRAM_LABELS = [
        'mba' => 'MBA',
        'law' => 'Law',
        'therapy' => 'Therapy',
        'cmhc' => 'CMHC',
        'mft' => 'MFT',
        'msw' => 'MSW',
        'clinical_psy' => 'Clinical Psy',
        'other' => 'Other',
    ];

    private const SERVICE_COLUMNS = [
        'Free Consultation',
        'Tutoring',
        'Program Insights',
        'Interview Prep',
        'Application Review',
        'Gap Year Planning',
        'Office Hours',
    ];

    public function build(): array
    {
        $mentors = Mentor::query()
            ->with([
                'user:id,name,email',
                'university:id,name,display_name',
                'bookings.service:id,service_name,price_1on1,price_1on3_per_person,price_1on5_per_person,office_hours_subscription_price',
                'rating:id,mentor_id,avg_stars',
            ])
            ->orderByDesc('approved_at')
            ->orderBy('id')
            ->get();

        $rows = $mentors->map(function (Mentor $mentor): array {
            $programLabel = self::PROGRAM_LABELS[$mentor->program_type ?? ''] ?? '-';
            $statusLabel = $this->formatStatus($mentor->status);
            $schoolLabel = $mentor->university?->display_name
                ?: $mentor->university?->name
                ?: $mentor->grad_school_display
                ?: '-';

            $bookings = $mentor->bookings;

            $countableBookings = $bookings->filter(fn (Booking $booking) => !in_array(
                (string) $booking->status,
                ['cancelled', 'cancelled_pending_refund'],
                true
            ));

            $serviceCounts = collect(self::SERVICE_COLUMNS)
                ->mapWithKeys(fn (string $serviceName) => [$serviceName => 0])
                ->all();

            $knownBookingAmounts = [];

            foreach ($countableBookings as $booking) {
                $serviceName = $booking->service?->service_name;

                if ($serviceName !== null && array_key_exists($serviceName, $serviceCounts)) {
                    $serviceCounts[$serviceName]++;
                }

                $bookingAmount = $this->resolveBookingAmount($booking);
                if ($bookingAmount !== null) {
                    $knownBookingAmounts[] = $bookingAmount;
                }
            }

            $ratingValue = $mentor->rating && (float) $mentor->rating->avg_stars > 0
                ? number_format((float) $mentor->rating->avg_stars, 1)
                : '-';

            return [
                'name' => $mentor->user?->name ?: '-',
                'email' => $mentor->user?->email ?: '-',
                'program' => $programLabel,
                'program_filter' => $programLabel !== '-' ? $programLabel : '',
                'school' => $schoolLabel,
                'total_meetings' => $countableBookings->count(),
                'total_revenue' => $knownBookingAmounts === [] ? null : array_sum($knownBookingAmounts),
                'free_consult' => $serviceCounts['Free Consultation'],
                'tutoring' => $serviceCounts['Tutoring'],
                'program_insights' => $serviceCounts['Program Insights'],
                'interview_prep' => $serviceCounts['Interview Prep'],
                'application_review' => $serviceCounts['Application Review'],
                'gap_year_planning' => $serviceCounts['Gap Year Planning'],
                'office_hours' => $serviceCounts['Office Hours'],
                'missed' => $bookings->where('status', 'no_show')->count(),
                'refunds' => $bookings->where('status', 'cancelled_pending_refund')->count(),
                'rating' => $ratingValue,
                'status' => $statusLabel,
                'status_filter' => $statusLabel !== '-' ? $statusLabel : '',
            ];
        })->values();

        return [
            'rows' => $rows,
            'program_options' => $rows->pluck('program_filter')->filter()->unique()->sort()->values(),
            'status_options' => $rows->pluck('status_filter')->filter()->unique()->sort()->values(),
        ];
    }

    private function resolveBookingAmount(Booking $booking): ?float
    {
        /** @var ServiceConfig|null $service */
        $service = $booking->service;

        if (! $service) {
            return null;
        }

        return match ($booking->session_type) {
            '1on1' => $service->price_1on1 !== null ? (float) $service->price_1on1 : null,
            '1on3' => $service->price_1on3_per_person !== null ? (float) $service->price_1on3_per_person : null,
            '1on5' => $service->price_1on5_per_person !== null ? (float) $service->price_1on5_per_person : null,
            'office_hours' => $service->office_hours_subscription_price !== null ? (float) $service->office_hours_subscription_price : null,
            default => null,
        };
    }

    private function formatStatus(?string $status): string
    {
        if ($status === null || trim($status) === '') {
            return '-';
        }

        return collect(explode('_', $status))
            ->map(fn (string $segment) => ucfirst($segment))
            ->implode(' ');
    }
}
