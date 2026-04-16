<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Services\CreditService;
use Modules\Settings\app\Models\Mentor;

class BookingService
{
    public function __construct(private readonly CreditService $creditService) {}

    public function createBooking(User $student, array $data): Booking
    {
        $mentor = Mentor::query()->findOrFail((int) $data['mentor_id']);
        $service = ServiceConfig::query()->findOrFail((int) $data['service_config_id']);

        $sessionType = $data['session_type'] ?? '1on1';
        $credits = $this->creditService->creditsNeeded($service, $sessionType);
        $requestedGroupSize = $this->requestedGroupSize($sessionType);
        $approvalStatus = $requestedGroupSize > 1 ? 'pending' : 'not_required';
        $amountCharged = $this->amountCharged($service, $sessionType);
        $pricingSnapshot = $this->pricingSnapshot($service, $sessionType, $credits, $amountCharged);
        $guestParticipants = $this->guestParticipants($data['guest_participants'] ?? []);

        return DB::transaction(function () use ($student, $mentor, $service, $sessionType, $credits, $requestedGroupSize, $approvalStatus, $amountCharged, $pricingSnapshot, $guestParticipants, $data) {
            $booking = Booking::create([
                'student_id' => $student->id,
                'mentor_id' => $mentor->id,
                'service_config_id' => $service->id,
                'session_type' => $sessionType,
                'requested_group_size' => $requestedGroupSize > 1 ? $requestedGroupSize : null,
                'session_at' => $data['session_at'],
                'session_timezone' => $data['session_timezone'] ?? 'UTC',
                'duration_minutes' => $service->duration_minutes,
                'meeting_type' => $data['meeting_type'] ?? 'zoom',
                'status' => $approvalStatus === 'pending' ? 'pending' : 'confirmed',
                'approval_status' => $approvalStatus,
                'credits_charged' => $credits,
                'amount_charged' => $amountCharged,
                'currency' => 'USD',
                'pricing_snapshot' => $pricingSnapshot,
                'is_group_payer' => $requestedGroupSize > 1,
                'group_payer_id' => $requestedGroupSize > 1 ? $student->id : null,
            ]);

            DB::table('booking_participants')->insert([
                'booking_id' => $booking->id,
                'user_id' => $student->id,
                'full_name' => $student->name,
                'email' => $student->email,
                'participant_role' => 'student',
                'is_primary' => true,
                'invite_status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($guestParticipants !== []) {
                DB::table('booking_participants')->insert(
                    array_map(function (array $participant) use ($booking) {
                        return [
                            'booking_id' => $booking->id,
                            'user_id' => null,
                            'full_name' => $participant['full_name'],
                            'email' => $participant['email'],
                            'participant_role' => 'guest',
                            'is_primary' => false,
                            'invite_status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $guestParticipants)
                );
            }

            $this->creditService->deduct($student, $credits, $booking, 'Booking charge');

            event(new BookingCreated($booking));

            return $booking->fresh(['mentor.user', 'service']);
        });
    }

    public function cancelBooking(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        if ((int) $booking->student_id !== (int) $actor->id && !$actor->hasRole('admin')) {
            throw new \RuntimeException('You are not allowed to cancel this booking.');
        }

        $booking->update([
            'status' => 'cancelled_pending_refund',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'cancelled_by' => $actor->id,
        ]);

        event(new BookingCancelled($booking));

        return $booking->fresh();
    }

    private function requestedGroupSize(string $sessionType): int
    {
        return match ($sessionType) {
            '1on3' => 3,
            '1on5' => 5,
            default => 1,
        };
    }

    private function amountCharged(ServiceConfig $service, string $sessionType): float
    {
        return match ($sessionType) {
            '1on3' => (float) ($service->price_1on3_total ?? 0),
            '1on5' => (float) ($service->price_1on5_total ?? 0),
            'office_hours' => 0.0,
            default => (float) ($service->price_1on1 ?? 0),
        };
    }

    private function pricingSnapshot(ServiceConfig $service, string $sessionType, int $credits, float $amountCharged): array
    {
        return [
            'service_config_id' => $service->id,
            'service_name' => $service->service_name,
            'service_slug' => $service->service_slug,
            'session_type' => $sessionType,
            'duration_minutes' => (int) $service->duration_minutes,
            'credits_charged' => $credits,
            'amount_charged' => round($amountCharged, 2),
            'currency' => 'USD',
            'price_1on1' => $service->price_1on1 !== null ? (float) $service->price_1on1 : null,
            'price_1on3_per_person' => $service->price_1on3_per_person !== null ? (float) $service->price_1on3_per_person : null,
            'price_1on3_total' => $service->price_1on3_total !== null ? (float) $service->price_1on3_total : null,
            'price_1on5_per_person' => $service->price_1on5_per_person !== null ? (float) $service->price_1on5_per_person : null,
            'price_1on5_total' => $service->price_1on5_total !== null ? (float) $service->price_1on5_total : null,
            'office_hours_subscription_price' => $service->office_hours_subscription_price !== null ? (float) $service->office_hours_subscription_price : null,
        ];
    }

    private function guestParticipants(array $participants): array
    {
        return collect($participants)
            ->filter(fn ($participant) => is_array($participant))
            ->map(function (array $participant) {
                return [
                    'full_name' => trim((string) ($participant['full_name'] ?? '')),
                    'email' => strtolower(trim((string) ($participant['email'] ?? ''))),
                ];
            })
            ->filter(fn (array $participant) => $participant['full_name'] !== '' && $participant['email'] !== '')
            ->values()
            ->all();
    }
}
