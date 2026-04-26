<?php

namespace Modules\Payments\app\Services;

use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\BookingPayment;

class MentorPayoutCalculator
{
    public function forBooking(Booking $booking, BookingPayment $payment): array
    {
        $dbRule = $this->databaseRuleFor($booking);

        if ($dbRule !== null) {
            return [
                'gross_amount' => $dbRule['gross'],
                'mentor_share_amount' => $dbRule['mentor_share'],
                'platform_fee_amount' => $dbRule['platform_fee'],
                'currency' => $this->currency($booking, $payment),
                'calculation_rule' => [
                    'type' => 'database_service_split',
                    'service_slug' => (string) $booking->service?->service_slug,
                    'session_type' => (string) $booking->session_type,
                    'gross' => $dbRule['gross'],
                    'mentor_share' => $dbRule['mentor_share'],
                    'platform_fee' => $dbRule['platform_fee'],
                ],
            ];
        }

        throw new \RuntimeException('Service payout split is not configured in admin pricing.');
    }

    public function forOfficeHoursBooking(Booking $booking): array
    {
        $booking->loadMissing(['service', 'officeHourSession']);
        $config = config('payments.office_hours', []);
        $mentorAmount = $booking->service?->office_hours_mentor_payout_per_attendee;
        if ($mentorAmount === null) {
            throw new \RuntimeException('Office Hours mentor payout is not configured in admin pricing.');
        }

        $mentorAmount = round((float) $mentorAmount, 2);
        $creditPackPrice = round((float) ($config['credit_pack_price'] ?? 200), 2);
        $creditPackCredits = (int) ($config['credit_pack_credits'] ?? 5);
        $creditCost = (int) ($config['credit_cost_per_attendee'] ?? 1);
        $attendeeNumber = (int) ($booking->officeHourSession?->current_occupancy ?? 0);

        return [
            'gross_amount' => 0.0,
            'mentor_share_amount' => $mentorAmount,
            'platform_fee_amount' => 0.0,
            'currency' => strtoupper((string) ($booking->currency ?: config('app.currency', 'USD'))),
            'calculation_rule' => [
                'type' => 'office_hours_per_attendee',
                'credit_pack_price' => $creditPackPrice,
                'credit_pack_credits' => $creditPackCredits,
                'credit_cost' => $creditCost,
                'mentor_amount_per_attendee' => $mentorAmount,
                'attendee_number' => $attendeeNumber > 0 ? $attendeeNumber : null,
            ],
        ];
    }

    private function databaseRuleFor(Booking $booking): ?array
    {
        $booking->loadMissing('service');

        if (! $booking->service) {
            return null;
        }

        $sessionType = (string) $booking->session_type;
        $fields = match ($sessionType) {
            '1on1' => ['price_1on1', 'platform_fee_1on1', 'mentor_payout_1on1'],
            '1on3' => ['price_1on3_total', 'platform_fee_1on3', 'mentor_payout_1on3'],
            '1on5' => ['price_1on5_total', 'platform_fee_1on5', 'mentor_payout_1on5'],
            default => null,
        };

        if ($fields === null) {
            return null;
        }

        [$priceField, $platformField, $mentorField] = $fields;
        $gross = $booking->service->{$priceField};
        $platformFee = $booking->service->{$platformField};
        $mentorShare = $booking->service->{$mentorField};

        if ($gross === null || $platformFee === null || $mentorShare === null) {
            return null;
        }

        return [
            'gross' => round((float) $gross, 2),
            'platform_fee' => round((float) $platformFee, 2),
            'mentor_share' => round((float) $mentorShare, 2),
        ];
    }

    private function currency(Booking $booking, BookingPayment $payment): string
    {
        return strtoupper((string) ($payment->currency ?: $booking->currency ?: config('app.currency', 'USD')));
    }
}
