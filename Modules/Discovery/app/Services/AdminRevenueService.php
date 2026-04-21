<?php

namespace Modules\Discovery\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\Booking;

class AdminRevenueService
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

    private const AVAILABLE_RANGES = [
        '30d' => 'Last 30 Days',
        '60d' => 'Last 60 Days',
        '6m' => 'Last 6 Months',
        '12m' => 'Last 12 Months',
        'all' => 'All Time',
    ];

    public function build(?string $selectedRange = null): array
    {
        $selectedRange = $this->normalizeRange($selectedRange);
        $startDate = $this->rangeStartDate($selectedRange);

        $revenueBookings = Booking::query()
            ->with([
                'mentor:id,user_id,program_type',
                'mentor.user:id,name',
            ])
            ->whereNotIn('status', ['cancelled', 'cancelled_pending_refund'])
            ->when($startDate, fn ($query) => $query->where('created_at', '>=', $startDate))
            ->get();

        $refundBookings = Booking::query()
            ->where('status', 'cancelled_pending_refund')
            ->when($startDate, fn ($query) => $query->where('cancelled_at', '>=', $startDate))
            ->get(['amount_charged']);

        $payouts = collect(DB::table('mentor_payouts')
            ->whereIn('status', ['paid', 'pending'])
            ->get(['amount', 'status', 'payout_date', 'created_at']))
            ->filter(fn ($payout) => $this->payoutFallsInRange($payout, $startDate))
            ->values();

        $grossRevenue = $this->sumMoney($revenueBookings->pluck('amount_charged'));
        $mentorPayoutsPaid = $this->sumMoney(
            $payouts->where('status', 'paid')->pluck('amount')
        );
        $mentorPayoutsPending = $this->sumMoney(
            $payouts->where('status', 'pending')->pluck('amount')
        );
        $mentorPayoutsTotal = round($mentorPayoutsPaid + $mentorPayoutsPending, 2);
        $refundAmount = $this->sumMoney($refundBookings->pluck('amount_charged'));

        return [
            'selected_range' => $selectedRange,
            'available_ranges' => collect(self::AVAILABLE_RANGES)
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'mentor_payouts_total' => $mentorPayoutsTotal,
                'mentor_payouts_paid' => $mentorPayoutsPaid,
                'mentor_payouts_pending' => $mentorPayoutsPending,
                'platform_revenue' => round($grossRevenue - $mentorPayoutsTotal, 2),
                'refund_amount' => $refundAmount,
                'timeframe_label' => self::AVAILABLE_RANGES[$selectedRange],
            ],
            'charts' => [
                'program_revenue' => $this->programRevenueChart($revenueBookings),
                'top_mentors' => $this->topMentorsChart($revenueBookings),
            ],
        ];
    }

    private function normalizeRange(?string $selectedRange): string
    {
        return array_key_exists((string) $selectedRange, self::AVAILABLE_RANGES)
            ? (string) $selectedRange
            : '30d';
    }

    private function rangeStartDate(string $selectedRange): ?Carbon
    {
        return match ($selectedRange) {
            '30d' => now()->subDays(30)->startOfDay(),
            '60d' => now()->subDays(60)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '12m' => now()->subMonths(12)->startOfDay(),
            'all' => null,
            default => now()->subDays(30)->startOfDay(),
        };
    }

    private function payoutFallsInRange(object $payout, ?Carbon $startDate): bool
    {
        if ($startDate === null) {
            return true;
        }

        $referenceDate = $payout->status === 'paid'
            ? ($payout->payout_date ?: $payout->created_at)
            : $payout->created_at;

        if ($referenceDate === null) {
            return false;
        }

        return Carbon::parse($referenceDate)->gte($startDate);
    }

    private function programRevenueChart(Collection $bookings): array
    {
        return $bookings
            ->map(function (Booking $booking): array {
                return [
                    'label' => self::PROGRAM_LABELS[$booking->mentor?->program_type ?? ''] ?? 'Unknown',
                    'value' => round((float) ($booking->amount_charged ?? 0), 2),
                ];
            })
            ->groupBy('label')
            ->map(fn (Collection $items, string $label): array => [
                'label' => $label,
                'value' => round($items->sum('value'), 2),
            ])
            ->sortBy([
                ['value', 'desc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function topMentorsChart(Collection $bookings): array
    {
        return $bookings
            ->map(function (Booking $booking): array {
                $mentorName = trim((string) ($booking->mentor?->user?->name ?? ''));

                return [
                    'key' => (string) ($booking->mentor_id ?? 'unknown'),
                    'label' => $mentorName !== '' ? $mentorName : 'Unknown',
                    'value' => round((float) ($booking->amount_charged ?? 0), 2),
                ];
            })
            ->groupBy('key')
            ->map(function (Collection $items): array {
                return [
                    'label' => (string) $items->first()['label'],
                    'value' => round($items->sum('value'), 2),
                ];
            })
            ->sortBy([
                ['value', 'desc'],
                ['label', 'asc'],
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function sumMoney(Collection $values): float
    {
        return round(
            $values->reduce(
                fn (float $carry, $value): float => $carry + (float) ($value ?? 0),
                0.0
            ),
            2
        );
    }
}
