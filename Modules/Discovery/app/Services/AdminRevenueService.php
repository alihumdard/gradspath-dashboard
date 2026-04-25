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
            ->whereIn('status', ['pending', 'paid', 'pending_release', 'ready', 'transferred', 'paid_out', 'failed'])
            ->get([
                'amount',
                'mentor_share_amount',
                'gross_amount',
                'platform_fee_amount',
                'status',
                'payout_date',
                'eligible_at',
                'transferred_at',
                'paid_out_at',
                'failed_at',
                'failure_reason',
                'created_at',
            ]))
            ->filter(fn ($payout) => $this->payoutFallsInRange($payout, $startDate))
            ->values();

        $grossRevenue = $this->sumMoney($revenueBookings->pluck('amount_charged'));
        $mentorPayoutsPaid = $this->sumMoney(
            $payouts->filter(fn ($payout) => in_array($payout->status, ['paid', 'transferred', 'paid_out'], true))
                ->map(fn ($payout) => $this->payoutAmount($payout))
        );
        $mentorPayoutsPending = $this->sumMoney(
            $payouts->filter(fn ($payout) => in_array($payout->status, ['pending', 'pending_release', 'ready', 'failed'], true))
                ->map(fn ($payout) => $this->payoutAmount($payout))
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
                'mentor_payouts_failed' => $this->sumMoney(
                    $payouts->where('status', 'failed')->map(fn ($payout) => $this->payoutAmount($payout))
                ),
                'platform_revenue' => round($grossRevenue - $mentorPayoutsTotal, 2),
                'refund_amount' => $refundAmount,
                'timeframe_label' => self::AVAILABLE_RANGES[$selectedRange],
            ],
            'charts' => [
                'program_revenue' => $this->programRevenueChart($revenueBookings),
                'top_mentors' => $this->topMentorsChart($revenueBookings),
            ],
            'recent_payouts' => $this->recentPayouts($selectedRange, $startDate),
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

        $referenceDate = in_array($payout->status, ['paid', 'transferred', 'paid_out'], true)
            ? ($payout->paid_out_at ?: $payout->payout_date ?: $payout->transferred_at ?: $payout->created_at)
            : (
                $payout->transferred_at
                ?: $payout->paid_out_at
                ?: $payout->eligible_at
                ?: $payout->failed_at
                ?: $payout->created_at
            );

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

    private function payoutAmount(object $payout): float
    {
        return round((float) ($payout->mentor_share_amount ?? $payout->amount ?? 0), 2);
    }

    private function recentPayouts(string $selectedRange, ?Carbon $startDate): array
    {
        return collect(DB::table('mentor_payouts')
            ->leftJoin('mentors', 'mentor_payouts.mentor_id', '=', 'mentors.id')
            ->leftJoin('users', 'mentors.user_id', '=', 'users.id')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where(function ($inner) use ($startDate) {
                    $inner->where('mentor_payouts.created_at', '>=', $startDate)
                        ->orWhere('mentor_payouts.transferred_at', '>=', $startDate)
                        ->orWhere('mentor_payouts.paid_out_at', '>=', $startDate)
                        ->orWhere('mentor_payouts.eligible_at', '>=', $startDate)
                        ->orWhere('mentor_payouts.failed_at', '>=', $startDate);
                });
            })
            ->orderByDesc(DB::raw('COALESCE(mentor_payouts.paid_out_at, mentor_payouts.transferred_at, mentor_payouts.failed_at, mentor_payouts.eligible_at, mentor_payouts.created_at)'))
            ->limit($selectedRange === 'all' ? 20 : 10)
            ->get([
                'mentor_payouts.id',
                'mentor_payouts.status',
                'mentor_payouts.amount',
                'mentor_payouts.gross_amount',
                'mentor_payouts.mentor_share_amount',
                'mentor_payouts.platform_fee_amount',
                'mentor_payouts.currency',
                'mentor_payouts.failure_reason',
                'mentor_payouts.transferred_at',
                'mentor_payouts.paid_out_at',
                'mentor_payouts.eligible_at',
                'mentor_payouts.failed_at',
                'users.name as mentor_name',
            ]))
            ->map(fn (object $row): array => [
                'mentor_name' => $row->mentor_name ?: 'Unknown',
                'status' => (string) $row->status,
                'gross_amount' => round((float) ($row->gross_amount ?? 0), 2),
                'mentor_share_amount' => $this->payoutAmount($row),
                'platform_fee_amount' => round((float) ($row->platform_fee_amount ?? 0), 2),
                'currency' => strtoupper((string) ($row->currency ?? 'USD')),
                'failure_reason' => $row->failure_reason,
                'reference_at' => $row->paid_out_at ?: $row->transferred_at ?: $row->failed_at ?: $row->eligible_at,
            ])
            ->all();
    }
}
