<?php

namespace Modules\Discovery\app\Services;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;

class AdminUsersTableService
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
        $students = User::query()
            ->role('student')
            ->with([
                'studentProfile.university:id,name,display_name',
                'bookings.service:id,service_name,price_1on1,price_1on3_per_person,price_1on5_per_person,office_hours_subscription_price',
            ])
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (User $student): array {
            $profile = $student->studentProfile;
            $programLabel = self::PROGRAM_LABELS[$profile?->program_type ?? ''] ?? '-';
            $institutionLabel = $profile?->university?->display_name
                ?: $profile?->university?->name
                ?: $profile?->institution_text
                ?: '-';

            $bookings = $student->bookings->filter(fn (Booking $booking) => !in_array(
                (string) $booking->status,
                ['cancelled', 'cancelled_pending_refund'],
                true
            ));

            $serviceCounts = collect(self::SERVICE_COLUMNS)
                ->mapWithKeys(fn (string $serviceName) => [$serviceName => 0])
                ->all();

            $knownBookingAmounts = [];

            foreach ($bookings as $booking) {
                $serviceName = $booking->service?->service_name;

                if ($serviceName !== null && array_key_exists($serviceName, $serviceCounts)) {
                    $serviceCounts[$serviceName]++;
                }

                $bookingAmount = $this->resolveBookingAmount($booking);
                if ($bookingAmount !== null) {
                    $knownBookingAmounts[] = $bookingAmount;
                }
            }

            $latestBookingAt = $bookings
                ->filter(fn (Booking $booking) => $booking->session_at !== null)
                ->sortByDesc('session_at')
                ->first()?->session_at;

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'program' => $programLabel,
                'program_filter' => $programLabel !== '-' ? $programLabel : '',
                'institution' => $institutionLabel,
                'institution_filter' => $institutionLabel !== '-' ? $institutionLabel : '',
                'total_meetings' => $bookings->count(),
                'booking_count' => $bookings->count(),
                'total_spent' => $knownBookingAmounts === [] ? null : array_sum($knownBookingAmounts),
                'free_consult' => $serviceCounts['Free Consultation'],
                'tutoring' => $serviceCounts['Tutoring'],
                'program_insights' => $serviceCounts['Program Insights'],
                'interview_prep' => $serviceCounts['Interview Prep'],
                'application_review' => $serviceCounts['Application Review'],
                'gap_year_planning' => $serviceCounts['Gap Year Planning'],
                'office_hours' => $serviceCounts['Office Hours'],
                'last_active' => $latestBookingAt?->diffForHumans() ?? '-',
            ];
        })->values();

        return [
            'rows' => $rows,
            'program_options' => $rows->pluck('program_filter')->filter()->unique()->sort()->values(),
            'institution_options' => $rows->pluck('institution_filter')->filter()->unique()->sort()->values(),
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
}
