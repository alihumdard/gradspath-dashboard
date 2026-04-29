<?php

namespace Modules\Payments\app\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Payments\app\Models\MentorPayout;

class AdminMentorPayoutsService
{
    private const STATUS_LABELS = [
        MentorPayout::STATUS_PENDING_RELEASE => 'Pending Release',
        MentorPayout::STATUS_READY => 'Ready',
        MentorPayout::STATUS_TRANSFERRED => 'Transferred',
        MentorPayout::STATUS_PAID_OUT => 'Paid Out',
        MentorPayout::STATUS_FAILED => 'Failed',
        MentorPayout::STATUS_REVERSED => 'Reversed',
    ];

    private const RANGE_LABELS = [
        '30d' => 'Last 30 Days',
        '60d' => 'Last 60 Days',
        '6m' => 'Last 6 Months',
        '12m' => 'Last 12 Months',
        'all' => 'All Time',
    ];

    public function build(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $query = $this->baseQuery($normalized);

        $payouts = (clone $query)
            ->orderByDesc(DB::raw($this->referenceDateExpression()))
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 30))
            ->withQueryString();

        return [
            'filters' => $normalized,
            'status_labels' => self::STATUS_LABELS,
            'range_options' => collect(self::RANGE_LABELS)
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'summary' => $this->summary((clone $query)->get()),
            'payouts' => $payouts->through(fn (MentorPayout $payout): array => $this->row($payout)),
        ];
    }

    public function detail(int $id): array
    {
        return $this->detailRow(
            MentorPayout::query()
                ->with($this->relations())
                ->findOrFail($id)
        );
    }

    private function baseQuery(array $filters): Builder
    {
        return MentorPayout::query()
            ->with($this->relations())
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('stripe_transfer_id', 'like', "%{$search}%")
                        ->orWhere('stripe_account_id', 'like', "%{$search}%")
                        ->orWhereHas('mentor.user', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('student', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['start_date'], function (Builder $query) use ($filters): void {
                $start = $filters['start_date'];
                $query->where(function (Builder $query) use ($start): void {
                    $query->where('created_at', '>=', $start)
                        ->orWhere('eligible_at', '>=', $start)
                        ->orWhere('transferred_at', '>=', $start)
                        ->orWhere('paid_out_at', '>=', $start)
                        ->orWhere('failed_at', '>=', $start);
                });
            });
    }

    private function relations(): array
    {
        return [
            'mentor:id,user_id,stripe_account_id,payouts_enabled,stripe_onboarding_complete,status',
            'mentor.user:id,name,email',
            'student:id,name,email',
            'booking:id,student_id,mentor_id,service_config_id,session_type,meeting_type,status,approval_status,session_at,completed_at,amount_charged,currency',
            'booking.service:id,service_name,service_slug',
            'bookingPayment:id,booking_id,status,amount,currency,stripe_checkout_session_id,stripe_payment_intent_id,payment_completed_at',
            'bookingPayment.service:id,service_name,service_slug',
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $range = array_key_exists((string) ($filters['range'] ?? ''), self::RANGE_LABELS)
            ? (string) $filters['range']
            : '30d';
        $status = array_key_exists((string) ($filters['status'] ?? ''), self::STATUS_LABELS)
            ? (string) $filters['status']
            : '';

        return [
            'status' => $status,
            'search' => trim((string) ($filters['q'] ?? '')),
            'range' => $range,
            'start_date' => $this->rangeStartDate($range),
        ];
    }

    private function rangeStartDate(string $range): ?Carbon
    {
        return match ($range) {
            '30d' => now()->subDays(30)->startOfDay(),
            '60d' => now()->subDays(60)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '12m' => now()->subMonths(12)->startOfDay(),
            'all' => null,
            default => now()->subDays(30)->startOfDay(),
        };
    }

    private function summary(Collection $payouts): array
    {
        return [
            'total_mentor_share' => $this->sumMoney($payouts),
            'pending_release' => $this->sumMoney($payouts->where('status', MentorPayout::STATUS_PENDING_RELEASE)),
            'ready_failed' => $this->sumMoney($payouts->whereIn('status', [MentorPayout::STATUS_READY, MentorPayout::STATUS_FAILED])),
            'transferred_paid_out' => $this->sumMoney($payouts->whereIn('status', [MentorPayout::STATUS_TRANSFERRED, MentorPayout::STATUS_PAID_OUT])),
            'reversed' => $this->sumMoney($payouts->where('status', MentorPayout::STATUS_REVERSED)),
            'count' => $payouts->count(),
        ];
    }

    private function row(MentorPayout $payout): array
    {
        return [
            'id' => $payout->id,
            'mentor_name' => $payout->mentor?->user?->name ?: 'Unknown mentor',
            'mentor_email' => $payout->mentor?->user?->email ?: '-',
            'student_name' => $payout->student?->name ?: 'Unknown student',
            'status' => (string) $payout->status,
            'status_label' => self::STATUS_LABELS[$payout->status] ?? ucfirst(str_replace('_', ' ', (string) $payout->status)),
            'gross_amount' => $this->money($payout->gross_amount),
            'mentor_share_amount' => $this->money($payout->mentor_share_amount ?? $payout->amount),
            'platform_fee_amount' => $this->money($payout->platform_fee_amount),
            'currency' => strtoupper((string) ($payout->currency ?? 'USD')),
            'reference_at' => $this->referenceDate($payout),
            'booking_id' => $payout->booking_id,
            'session_type' => $payout->booking?->session_type ?: '-',
            'failure_reason' => $payout->failure_reason,
        ];
    }

    private function detailRow(MentorPayout $payout): array
    {
        return array_merge($this->row($payout), [
            'stripe_account_id' => $payout->stripe_account_id ?: $payout->mentor?->stripe_account_id,
            'payouts_enabled' => (bool) $payout->mentor?->payouts_enabled,
            'stripe_onboarding_complete' => (bool) $payout->mentor?->stripe_onboarding_complete,
            'mentor_status' => $payout->mentor?->status ?: '-',
            'student_email' => $payout->student?->email ?: '-',
            'booking_status' => $payout->booking?->status ?: '-',
            'booking_approval_status' => $payout->booking?->approval_status ?: '-',
            'booking_session_type' => $payout->booking?->session_type ?: '-',
            'booking_meeting_type' => $payout->booking?->meeting_type ?: '-',
            'booking_session_at' => $payout->booking?->session_at,
            'booking_completed_at' => $payout->booking?->completed_at,
            'booking_amount_charged' => $this->money($payout->booking?->amount_charged),
            'service_name' => $payout->booking?->service?->service_name
                ?: $payout->bookingPayment?->service?->service_name
                ?: '-',
            'payment_status' => $payout->bookingPayment?->status ?: '-',
            'payment_amount' => $this->money($payout->bookingPayment?->amount),
            'payment_completed_at' => $payout->bookingPayment?->payment_completed_at,
            'stripe_checkout_session_id' => $payout->bookingPayment?->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $payout->bookingPayment?->stripe_payment_intent_id,
            'stripe_transfer_id' => $payout->stripe_transfer_id,
            'stripe_balance_transaction_id' => $payout->stripe_balance_transaction_id,
            'eligible_at' => $payout->eligible_at,
            'transferred_at' => $payout->transferred_at,
            'paid_out_at' => $payout->paid_out_at,
            'failed_at' => $payout->failed_at,
            'payout_date' => $payout->payout_date,
            'attempt_count' => (int) $payout->attempt_count,
            'last_attempt_at' => $payout->last_attempt_at,
            'calculation_rule' => $this->calculationRule($payout->calculation_rule),
            'created_at' => $payout->created_at,
            'updated_at' => $payout->updated_at,
        ]);
    }

    private function calculationRule(?array $rule): array
    {
        if (! $rule) {
            return [];
        }

        return collect($rule)
            ->map(fn ($value, string $key): array => [
                'label' => str($key)->replace('_', ' ')->title()->toString(),
                'value' => is_scalar($value) || $value === null ? (string) ($value ?? '-') : json_encode($value),
            ])
            ->values()
            ->all();
    }

    private function referenceDate(MentorPayout $payout): ?Carbon
    {
        return $payout->paid_out_at
            ?: $payout->transferred_at
            ?: $payout->failed_at
            ?: $payout->eligible_at
            ?: $payout->payout_date
            ?: $payout->created_at;
    }

    private function referenceDateExpression(): string
    {
        return 'COALESCE(paid_out_at, transferred_at, failed_at, eligible_at, payout_date, created_at)';
    }

    private function sumMoney(Collection $payouts): float
    {
        return round(
            $payouts->reduce(
                fn (float $carry, MentorPayout $payout): float => $carry + $this->money($payout->mentor_share_amount ?? $payout->amount),
                0.0
            ),
            2
        );
    }

    private function money($amount): float
    {
        return round((float) ($amount ?? 0), 2);
    }
}
