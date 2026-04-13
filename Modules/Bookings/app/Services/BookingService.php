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

        return DB::transaction(function () use ($student, $mentor, $service, $sessionType, $credits, $data) {
            $booking = Booking::create([
                'student_id' => $student->id,
                'mentor_id' => $mentor->id,
                'service_config_id' => $service->id,
                'session_type' => $sessionType,
                'session_at' => $data['session_at'],
                'session_timezone' => $data['session_timezone'] ?? 'UTC',
                'duration_minutes' => $service->duration_minutes,
                'meeting_type' => $data['meeting_type'] ?? 'zoom',
                'status' => 'confirmed',
                'credits_charged' => $credits,
                'is_group_payer' => false,
            ]);

            DB::table('booking_participants')->updateOrInsert(
                [
                    'booking_id' => $booking->id,
                    'user_id' => $student->id,
                ],
                [
                    'participant_role' => 'student',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

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
}
