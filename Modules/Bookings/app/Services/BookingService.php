<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\OfficeHours\app\Models\OfficeHourSession;
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
        $sessionType = (string) ($data['session_type'] ?? '1on1');
        $credits = $this->creditService->creditsNeeded($service, $sessionType);
        $requestedGroupSize = $this->requestedGroupSize($sessionType);
        $approvalStatus = $requestedGroupSize > 1 ? 'pending' : 'not_required';
        $amountCharged = $this->amountCharged($service, $sessionType);
        $pricingSnapshot = $this->pricingSnapshot($service, $sessionType, $credits, $amountCharged);
        $guestParticipants = $this->guestParticipants($data['guest_participants'] ?? []);

        return DB::transaction(function () use (
            $student,
            $mentor,
            $service,
            $sessionType,
            $credits,
            $requestedGroupSize,
            $approvalStatus,
            $amountCharged,
            $pricingSnapshot,
            $guestParticipants,
            $data
        ) {
            [$sessionAt, $sessionTimezone, $durationMinutes, $slotId, $officeHourSessionId] = $sessionType === 'office_hours'
                ? $this->reserveOfficeHourSession($student, $mentor, $data)
                : $this->reserveAvailabilitySlot($mentor, $service, $sessionType, $requestedGroupSize, $data);

            $booking = Booking::create([
                'student_id' => $student->id,
                'mentor_id' => $mentor->id,
                'service_config_id' => $service->id,
                'mentor_availability_slot_id' => $slotId,
                'office_hour_session_id' => $officeHourSessionId,
                'session_type' => $sessionType,
                'requested_group_size' => $requestedGroupSize > 1 ? $requestedGroupSize : null,
                'session_at' => $sessionAt,
                'session_timezone' => $sessionTimezone,
                'duration_minutes' => $durationMinutes,
                'meeting_type' => $data['meeting_type'] ?? 'zoom',
                'status' => $approvalStatus === 'pending' ? 'pending' : 'confirmed',
                'approval_status' => $sessionType === 'office_hours' ? 'not_required' : $approvalStatus,
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

        DB::transaction(function () use ($booking, $actor, $reason) {
            $booking->update([
                'status' => 'cancelled_pending_refund',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancelled_by' => $actor->id,
            ]);

            if ($booking->mentor_availability_slot_id) {
                $slot = MentorAvailabilitySlot::query()->lockForUpdate()->find($booking->mentor_availability_slot_id);

                if ($slot) {
                    $slot->booked_participants_count = 0;
                    $slot->is_booked = false;
                    $slot->save();
                }
            }

            if ($booking->office_hour_session_id) {
                $session = OfficeHourSession::query()->lockForUpdate()->find($booking->office_hour_session_id);

                if ($session) {
                    $session->current_occupancy = max(((int) $session->current_occupancy) - 1, 0);
                    $session->is_full = $session->current_occupancy >= (int) $session->max_spots;
                    $session->service_locked = $session->current_occupancy >= 2;
                    $session->save();
                }
            }
        });

        event(new BookingCancelled($booking));

        return $booking->fresh();
    }

    private function reserveAvailabilitySlot(
        Mentor $mentor,
        ServiceConfig $service,
        string $sessionType,
        int $requestedGroupSize,
        array $data
    ): array {
        $slot = MentorAvailabilitySlot::query()
            ->lockForUpdate()
            ->findOrFail((int) $data['mentor_availability_slot_id']);

        if ((int) $slot->mentor_id !== (int) $mentor->id) {
            throw new \RuntimeException('This time slot does not belong to the selected mentor.');
        }

        if ((int) ($slot->service_config_id ?? 0) !== (int) $service->id) {
            throw new \RuntimeException('This time slot does not match the selected service.');
        }

        if ($slot->session_type !== $sessionType) {
            throw new \RuntimeException('This time slot does not match the selected meeting size.');
        }

        if (!$slot->is_active || $slot->is_blocked || $slot->is_booked) {
            throw new \RuntimeException('This time slot is no longer available.');
        }

        if ($slot->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            throw new \RuntimeException('This time slot has already been reserved.');
        }

        if ((int) $slot->max_participants < $requestedGroupSize) {
            throw new \RuntimeException('This time slot cannot support the selected meeting size.');
        }

        $sessionAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time, $slot->timezone ?: config('app.timezone'));
        if ($sessionAt->lte(now())) {
            throw new \RuntimeException('Please choose a future time slot.');
        }

        $slot->booked_participants_count = $requestedGroupSize;
        $slot->is_booked = true;
        $slot->save();

        return [
            $sessionAt,
            $slot->timezone ?: 'UTC',
            max($sessionAt->diffInMinutes(Carbon::parse($slot->slot_date->toDateString().' '.$slot->end_time, $slot->timezone ?: config('app.timezone'))), 1),
            $slot->id,
            null,
        ];
    }

    private function reserveOfficeHourSession(User $student, Mentor $mentor, array $data): array
    {
        $session = OfficeHourSession::query()
            ->with('schedule')
            ->lockForUpdate()
            ->findOrFail((int) $data['office_hour_session_id']);

        if ((int) ($session->schedule?->mentor_id ?? 0) !== (int) $mentor->id) {
            throw new \RuntimeException('This office-hours session does not belong to the selected mentor.');
        }

        if ($session->status !== 'upcoming' || $session->is_full) {
            throw new \RuntimeException('This office-hours session is no longer bookable.');
        }

        $sessionAt = Carbon::parse($session->session_date->toDateString().' '.$session->start_time, $session->timezone ?: config('app.timezone'));
        if ($sessionAt->lte(now())) {
            throw new \RuntimeException('Please choose an upcoming office-hours session.');
        }

        if (
            Booking::query()
                ->where('office_hour_session_id', $session->id)
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists()
        ) {
            throw new \RuntimeException('You have already booked this office-hours session.');
        }

        if ((int) $session->current_occupancy >= (int) $session->max_spots) {
            throw new \RuntimeException('This office-hours session is full.');
        }

        $session->current_occupancy = (int) $session->current_occupancy + 1;
        $session->is_full = $session->current_occupancy >= (int) $session->max_spots;
        if (!$session->first_booker_id) {
            $session->first_booker_id = $student->id;
            $session->first_booked_at = now();
        }
        $session->service_locked = $session->current_occupancy >= 2;
        $session->save();

        return [
            $sessionAt,
            $session->timezone ?: 'UTC',
            45,
            null,
            $session->id,
        ];
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
