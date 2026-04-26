<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;

class AdminServicesTableService
{
    public function build(): array
    {
        $services = ServiceConfig::query()
            ->with('bookings')
            ->orderBy('sort_order')
            ->orderBy('service_name')
            ->get();

        $rows = $services->map(function (ServiceConfig $service): array {
            $bookings = $service->bookings->filter(fn (Booking $booking) => !in_array(
                (string) $booking->status,
                ['cancelled', 'cancelled_pending_refund'],
                true
            ));

            $bookingAmounts = [];
            foreach ($bookings as $booking) {
                $bookingAmount = $this->resolveBookingAmount($service, $booking);
                if ($bookingAmount !== null) {
                    $bookingAmounts[] = $bookingAmount;
                }
            }

            return [
                'id' => $service->id,
                'service_name' => $service->service_name ?: '-',
                'format' => $this->formatServiceFormat($service),
                'set_price' => $this->formatSetPrice($service),
                'bookings' => $bookings->count(),
                'revenue' => $bookingAmounts === [] ? null : array_sum($bookingAmounts),
                'mentors_offering' => $this->countActiveMentorsOffering($service->id),
            ];
        })->values();

        $rankedRows = $rows
            ->sortBy([
                ['bookings', 'desc'],
                ['service_name', 'asc'],
            ])
            ->values()
            ->map(function (array $row, int $index): array {
                $row['popularity_rank'] = $row['bookings'] > 0 ? $index + 1 : '-';
                return $row;
            })
            ->keyBy('id');

        return $rows->map(function (array $row) use ($rankedRows): array {
            $row['popularity_rank'] = $rankedRows[$row['id']]['popularity_rank'] ?? '-';
            return $row;
        })->values()->all();
    }

    private function countActiveMentorsOffering(int $serviceId): int
    {
        return (int) DB::table('mentor_services')
            ->where('service_config_id', $serviceId)
            ->where('is_active', true)
            ->count();
    }

    private function formatServiceFormat(ServiceConfig $service): string
    {
        if ($service->is_office_hours) {
            $duration = $service->duration_minutes !== null ? "{$service->duration_minutes} min" : '-';
            return "{$duration} subscription";
        }

        $parts = [];

        if ($service->duration_minutes !== null) {
            $parts[] = "{$service->duration_minutes} min";
        }

        $sessionTypes = [];
        if ($service->price_1on1 !== null) {
            $sessionTypes[] = '1:1';
        }
        if ($service->price_1on3_per_person !== null) {
            $sessionTypes[] = '1:3';
        }
        if ($service->price_1on5_per_person !== null) {
            $sessionTypes[] = '1:5';
        }

        if ($sessionTypes !== []) {
            $parts[] = implode(' / ', $sessionTypes);
        }

        return $parts === [] ? '-' : implode(' • ', $parts);
    }

    private function formatSetPrice(ServiceConfig $service): string
    {
        if ($service->is_office_hours) {
            return $service->office_hours_subscription_price !== null
                ? '$' . number_format((float) $service->office_hours_subscription_price, 0) . ' subscription'
                : '-';
        }

        $parts = [];

        if ($service->price_1on1 !== null) {
            $parts[] = '1:1 $' . $this->formatDecimal($service->price_1on1);
        }

        if ($service->price_1on3_per_person !== null) {
            $perPerson = (float) $service->price_1on3_per_person;
            $total = $service->price_1on3_total !== null ? (float) $service->price_1on3_total : $perPerson * 3;
            $parts[] = '1:3 $' . $this->formatDecimal($perPerson) . ' pp / $' . $this->formatDecimal($total) . ' total';
        }

        if ($service->price_1on5_per_person !== null) {
            $perPerson = (float) $service->price_1on5_per_person;
            $total = $service->price_1on5_total !== null ? (float) $service->price_1on5_total : $perPerson * 5;
            $parts[] = '1:5 $' . $this->formatDecimal($perPerson) . ' pp / $' . $this->formatDecimal($total) . ' total';
        }

        return $parts === [] ? '-' : implode(' • ', $parts);
    }

    private function resolveBookingAmount(ServiceConfig $service, Booking $booking): ?float
    {
        return match ($booking->session_type) {
            '1on1' => $service->price_1on1 !== null ? (float) $service->price_1on1 : null,
            '1on3' => $service->price_1on3_total !== null ? (float) $service->price_1on3_total : ($service->price_1on3_per_person !== null ? (float) $service->price_1on3_per_person * 3 : null),
            '1on5' => $service->price_1on5_total !== null ? (float) $service->price_1on5_total : ($service->price_1on5_per_person !== null ? (float) $service->price_1on5_per_person * 5 : null),
            'office_hours' => $service->office_hours_subscription_price !== null ? (float) $service->office_hours_subscription_price : null,
            default => null,
        };
    }

    private function formatDecimal(float|string $value): string
    {
        $number = (float) $value;
        return number_format($number, fmod($number, 1.0) === 0.0 ? 0 : 2);
    }
}
