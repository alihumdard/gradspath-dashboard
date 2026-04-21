<?php

namespace Modules\Discovery\app\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class AdminOverviewService
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

    public function __construct(
        private readonly AdminRevenueService $revenue,
    ) {}

    public function build(): array
    {
        $now = now();
        $last30Days = $now->copy()->subDays(30)->startOfDay();
        $last7Days = $now->copy()->subDays(7)->startOfDay();
        $sixMonthStart = $now->copy()->startOfMonth()->subMonths(5);

        $validBookingsLast30Days = Booking::query()
            ->with([
                'mentor.user:id,name',
                'service:id,service_name,price_1on1,price_1on3_per_person,price_1on5_per_person,office_hours_subscription_price,is_office_hours',
            ])
            ->whereNotIn('status', ['cancelled', 'cancelled_pending_refund'])
            ->where('created_at', '>=', $last30Days)
            ->get();

        $validBookingsLast7Days = Booking::query()
            ->whereNotIn('status', ['cancelled', 'cancelled_pending_refund'])
            ->where('created_at', '>=', $last7Days)
            ->count();

        $refundBookingsLast30Days = Booking::query()
            ->where('status', 'cancelled_pending_refund')
            ->where('cancelled_at', '>=', $last30Days)
            ->get(['amount_charged']);

        $sixMonthBookings = Booking::query()
            ->where('created_at', '>=', $sixMonthStart)
            ->get(['created_at', 'status', 'amount_charged']);

        $revenueSummary = $this->revenue->build('30d')['summary'];

        $summary = [
            'total_users' => User::query()->role('student')->count(),
            'new_users_30d' => User::query()->role('student')->where('created_at', '>=', $last30Days)->count(),
            'active_mentors' => Mentor::query()->where('status', 'active')->count(),
            'inactive_mentors' => Mentor::query()->where('status', '!=', 'active')->orWhereNull('status')->count(),
            'bookings_30d' => $validBookingsLast30Days->count(),
            'bookings_7d' => $validBookingsLast7Days,
            'gross_revenue_30d' => $revenueSummary['gross_revenue'] ?? 0,
            'platform_revenue_30d' => $revenueSummary['platform_revenue'] ?? 0,
            'refund_amount_30d' => $revenueSummary['refund_amount'] ?? 0,
            'refund_requests_30d' => $refundBookingsLast30Days->count(),
        ];

        return [
            'summary' => $summary,
            'charts' => [
                'bookings_over_time' => $this->monthlyBookingsChart($sixMonthBookings, $sixMonthStart),
                'revenue_over_time' => $this->monthlyRevenueChart($sixMonthBookings, $sixMonthStart),
            ],
            'tables' => [
                'top_mentors' => $this->topMentors($validBookingsLast30Days),
                'top_services' => $this->topServices($validBookingsLast30Days),
            ],
        ];
    }

    private function monthlyBookingsChart(Collection $bookings, Carbon $startMonth): array
    {
        return $this->monthlyBuckets($bookings, $startMonth, function (Collection $items): int {
            return $items
                ->reject(fn (Booking $booking) => in_array((string) $booking->status, ['cancelled', 'cancelled_pending_refund'], true))
                ->count();
        });
    }

    private function monthlyRevenueChart(Collection $bookings, Carbon $startMonth): array
    {
        return $this->monthlyBuckets($bookings, $startMonth, function (Collection $items): float {
            return round(
                $items
                    ->reject(fn (Booking $booking) => in_array((string) $booking->status, ['cancelled', 'cancelled_pending_refund'], true))
                    ->sum(fn (Booking $booking): float => (float) ($booking->amount_charged ?? 0)),
                2
            );
        });
    }

    private function monthlyBuckets(Collection $bookings, Carbon $startMonth, callable $resolver): array
    {
        return collect(range(0, 5))
            ->map(function (int $offset) use ($bookings, $startMonth, $resolver): array {
                $month = $startMonth->copy()->addMonths($offset);
                $monthItems = $bookings->filter(function (Booking $booking) use ($month): bool {
                    if (! $booking->created_at) {
                        return false;
                    }

                    return $booking->created_at->format('Y-m') === $month->format('Y-m');
                })->values();

                return [
                    'label' => $month->format('M'),
                    'value' => $resolver($monthItems),
                ];
            })
            ->all();
    }

    private function topMentors(Collection $bookings): array
    {
        return $bookings
            ->groupBy('mentor_id')
            ->map(function (Collection $items): array {
                /** @var Booking|null $first */
                $first = $items->first();
                $mentorName = trim((string) ($first?->mentor?->user?->name ?? ''));

                return [
                    'mentor' => $mentorName !== '' ? $mentorName : 'Unknown',
                    'program' => self::PROGRAM_LABELS[$first?->mentor?->program_type ?? ''] ?? 'Unknown',
                    'meetings' => $items->count(),
                    'revenue' => round($items->sum(fn (Booking $booking): float => (float) ($booking->amount_charged ?? 0)), 2),
                ];
            })
            ->sortBy([
                ['revenue', 'desc'],
                ['mentor', 'asc'],
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function topServices(Collection $bookings): array
    {
        return $bookings
            ->groupBy('service_config_id')
            ->map(function (Collection $items): array {
                /** @var Booking|null $first */
                $first = $items->first();
                /** @var ServiceConfig|null $service */
                $service = $first?->service;
                $serviceName = trim((string) ($service?->service_name ?? ''));

                return [
                    'service' => $serviceName !== '' ? $serviceName : 'Unknown',
                    'bookings' => $items->count(),
                    'revenue' => round($items->sum(fn (Booking $booking): float => (float) ($booking->amount_charged ?? 0)), 2),
                    'set_price' => $this->formatSetPrice($service),
                ];
            })
            ->sortBy([
                ['bookings', 'desc'],
                ['service', 'asc'],
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function formatSetPrice(?ServiceConfig $service): string
    {
        if (! $service) {
            return '-';
        }

        if ($service->is_office_hours) {
            return $service->office_hours_subscription_price !== null
                ? '$' . $this->formatDecimal($service->office_hours_subscription_price) . ' subscription'
                : '-';
        }

        $parts = [];

        if ($service->price_1on1 !== null) {
            $parts[] = '1:1 $' . $this->formatDecimal($service->price_1on1);
        }

        if ($service->price_1on3_per_person !== null) {
            $parts[] = '1:3 $' . $this->formatDecimal($service->price_1on3_per_person) . ' pp';
        }

        if ($service->price_1on5_per_person !== null) {
            $parts[] = '1:5 $' . $this->formatDecimal($service->price_1on5_per_person) . ' pp';
        }

        return $parts === [] ? '-' : implode(' • ', $parts);
    }

    private function formatDecimal(float|string $value): string
    {
        $number = (float) $value;

        return number_format($number, fmod($number, 1.0) === 0.0 ? 0 : 2);
    }
}
