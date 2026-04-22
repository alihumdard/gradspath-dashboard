<?php

namespace Modules\Discovery\app\Services;

use Modules\Bookings\app\Models\Booking;

class AdminRankingsService
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

    private const SESSION_TYPE_LABELS = [
        '1on1' => '1 on 1',
        '1on3' => '1 on 3',
        '1on5' => '1 on 5',
        'office_hours' => 'Office Hours',
    ];

    public function build(): array
    {
        $bookings = Booking::query()
            ->with([
                'student.studentProfile.university:id,name,display_name',
                'mentor.university:id,name,display_name',
                'service:id,service_name',
            ])
            ->whereNotIn('status', ['cancelled', 'cancelled_pending_refund'])
            ->get();

        return [
            'programs' => $this->rankedItems(
                $bookings->map(fn (Booking $booking): string => $this->programLabel($booking))
            ),
            'services' => $this->rankedItems(
                $bookings->map(fn (Booking $booking): string => $this->serviceLabel($booking))
            ),
            'student_schools' => $this->rankedItems(
                $bookings->map(fn (Booking $booking): string => $this->studentSchoolLabel($booking)),
                5
            ),
            'mentor_schools' => $this->rankedItems(
                $bookings->map(fn (Booking $booking): string => $this->mentorSchoolLabel($booking)),
                5
            ),
            'meeting_sizes' => $this->rankedItems(
                $bookings->map(fn (Booking $booking): string => $this->meetingSizeLabel($booking))
            ),
        ];
    }

    private function rankedItems($labels, ?int $limit = null): array
    {
        $items = $labels
            ->countBy()
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'count' => $count,
            ])
            ->sortBy([
                ['count', 'desc'],
                ['label', 'asc'],
            ])
            ->values();

        if ($limit !== null) {
            $items = $items->take($limit)->values();
        }

        return $items
            ->map(function (array $item, int $index): array {
                return [
                    'label' => $item['label'],
                    'count' => $item['count'],
                    'rank' => $index + 1,
                ];
            })
            ->all();
    }

    private function programLabel(Booking $booking): string
    {
        $programType = $booking->student?->studentProfile?->program_type;

        return self::PROGRAM_LABELS[$programType ?? ''] ?? 'Unknown';
    }

    private function serviceLabel(Booking $booking): string
    {
        $serviceName = trim((string) ($booking->service?->service_name ?? ''));

        return $serviceName !== '' ? $serviceName : 'Unknown';
    }

    private function studentSchoolLabel(Booking $booking): string
    {
        $profile = $booking->student?->studentProfile;
        $schoolName = $profile?->university?->display_name
            ?: $profile?->university?->name
            ?: $profile?->institution_text;

        $schoolName = trim((string) $schoolName);

        return $schoolName !== '' ? $schoolName : 'Unknown';
    }

    private function mentorSchoolLabel(Booking $booking): string
    {
        $mentor = $booking->mentor;
        $schoolName = $mentor?->university?->display_name
            ?: $mentor?->university?->name
            ?: $mentor?->grad_school_display;

        $schoolName = trim((string) $schoolName);

        return $schoolName !== '' ? $schoolName : 'Unknown';
    }

    private function meetingSizeLabel(Booking $booking): string
    {
        return self::SESSION_TYPE_LABELS[$booking->session_type ?? ''] ?? 'Unknown';
    }
}
