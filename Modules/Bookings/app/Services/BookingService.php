<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Exceptions\BookingException;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Services\CreditService;
use Modules\Settings\app\Models\Mentor;

class BookingService
{
    public function __construct(private readonly CreditService $creditService) {}

    public function createBooking(User $booker, array $data, array $options = []): Booking
    {
        $mentor = Mentor::query()->findOrFail((int) $data['mentor_id']);
        $service = ServiceConfig::query()->findOrFail((int) $data['service_config_id']);
        $sessionType = (string) ($data['session_type'] ?? '1on1');
        $chargeCredits = (bool) ($options['charge_credits'] ?? true);
        $credits = $chargeCredits ? $this->creditService->creditsNeeded($service, $sessionType) : 0;
        $requestedGroupSize = $this->requestedGroupSize($sessionType);
        $approvalStatus = $requestedGroupSize > 1 ? 'pending' : 'not_required';
        $amountCharged = $this->amountCharged($service, $sessionType);
        $pricingSnapshot = $this->pricingSnapshot($service, $sessionType, $credits, $amountCharged);
        $guestParticipants = $this->guestParticipants($data['guest_participants'] ?? []);
        $this->assertBookerCanBookMentor($booker, $mentor);
        $this->assertMentorOffersService($mentor, $service);

        $booking = DB::transaction(function () use (
            $booker,
            $mentor,
            $service,
            $sessionType,
            $chargeCredits,
            $credits,
            $requestedGroupSize,
            $approvalStatus,
            $amountCharged,
            $pricingSnapshot,
            $guestParticipants,
            $data
        ) {
            [$sessionAtUtc, $sessionTimezone, $durationMinutes, $slotId, $officeHourSessionId] = $sessionType === 'office_hours'
                ? $this->reserveOfficeHourSession($booker, $mentor, $service, $data)
                : $this->reserveAvailabilitySlot($mentor, $service, $sessionType, $requestedGroupSize, $data);

            $booking = Booking::create([
                'student_id' => $booker->id,
                'mentor_id' => $mentor->id,
                'service_config_id' => $service->id,
                'mentor_availability_slot_id' => $slotId,
                'office_hour_session_id' => $officeHourSessionId,
                'session_type' => $sessionType,
                'requested_group_size' => $requestedGroupSize > 1 ? $requestedGroupSize : null,
                'session_at' => $sessionAtUtc,
                'session_timezone' => $sessionTimezone,
                'duration_minutes' => $durationMinutes,
                'meeting_type' => $data['meeting_type'] ?? 'zoom',
                'status' => $approvalStatus === 'pending' ? 'pending' : 'confirmed',
                'approval_status' => $sessionType === 'office_hours' ? 'not_required' : $approvalStatus,
                'credits_charged' => $credits,
                'amount_charged' => $amountCharged,
                'currency' => $this->currency(),
                'pricing_snapshot' => $pricingSnapshot,
                'is_group_payer' => $requestedGroupSize > 1,
                'group_payer_id' => $requestedGroupSize > 1 ? $booker->id : null,
            ]);

            DB::table('booking_participants')->insert([
                'booking_id' => $booking->id,
                'user_id' => $booker->id,
                'full_name' => $booker->name,
                'email' => $booker->email,
                'participant_role' => $booker->hasRole('mentor') ? 'booker' : 'student',
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

            if ($chargeCredits && $credits > 0) {
                $this->creditService->deduct($booker, $credits, $booking, 'Booking charge');
            }

            return $booking->fresh(['mentor.user', 'service', 'booker']);
        });

        event(new BookingCreated($booking));

        return $booking->fresh(['mentor.user', 'service', 'booker']);
    }

    public function cancelBooking(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        $isAdmin = $actor->hasRole('admin');
        $isBooker = (int) $booking->student_id === (int) $actor->id;
        $isMentor = (int) ($booking->mentor?->user_id ?? 0) === (int) $actor->id;

        if (! $isBooker && ! $isMentor && ! $isAdmin) {
            throw new BookingException('You are not allowed to cancel this booking.');
        }

        if (! in_array((string) $booking->status, ['pending', 'confirmed'], true)) {
            throw new BookingException('This booking can no longer be cancelled.');
        }

        if (! $booking->session_at?->isFuture()) {
            throw new BookingException('Only upcoming bookings can be cancelled.');
        }

        if (! $isAdmin && ! $booking->isSelfCancellationWindowOpen()) {
            throw new BookingException('Self-service cancellation closes 24 hours before the meeting. Please contact support if you need help.');
        }

        DB::transaction(function () use ($booking, $actor, $reason) {
            $booking->update([
                'status' => 'cancelled',
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

        event(new BookingCancelled($booking->fresh(['mentor.user', 'service', 'booker', 'participantRecords', 'cancelledBy'])));

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
            throw new BookingException('This time slot does not belong to the selected mentor.');
        }

        if ($slot->service_config_id !== null && (int) $slot->service_config_id !== (int) $service->id) {
            throw new BookingException('This time slot does not match the selected service.');
        }

        if ($slot->session_type !== $sessionType) {
            throw new BookingException('This time slot does not match the selected meeting size.');
        }

        if ($slot->service_config_id === null && $sessionType !== '1on1') {
            throw new BookingException('This time slot is only available for 1 on 1 bookings.');
        }

        if (! $slot->is_active || $slot->is_blocked || $slot->is_booked) {
            throw new BookingException('This time slot is no longer available.');
        }

        if ($slot->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            throw new BookingException('This time slot has already been reserved.');
        }

        if ((int) $slot->max_participants < $requestedGroupSize) {
            throw new BookingException('This time slot cannot support the selected meeting size.');
        }

        $sessionAt = Carbon::parse($slot->slot_date->toDateString().' '.$slot->start_time, $slot->timezone ?: config('app.timezone'));
        if ($sessionAt->lte(now())) {
            throw new BookingException('Please choose a future time slot.');
        }

        $sessionAtUtc = $sessionAt->copy()->utc();
        $sessionEndUtc = Carbon::parse($slot->slot_date->toDateString().' '.$slot->end_time, $slot->timezone ?: config('app.timezone'))->utc();

        $slot->booked_participants_count = $requestedGroupSize;
        $slot->is_booked = true;
        $slot->save();

        return [
            $sessionAtUtc,
            $slot->timezone ?: 'UTC',
            max($sessionAtUtc->diffInMinutes($sessionEndUtc), 1),
            $slot->id,
            null,
        ];
    }

    private function reserveOfficeHourSession(User $booker, Mentor $mentor, ServiceConfig $service, array $data): array
    {
        $session = OfficeHourSession::query()
            ->with('schedule')
            ->lockForUpdate()
            ->findOrFail((int) $data['office_hour_session_id']);

        if ((int) ($session->schedule?->mentor_id ?? 0) !== (int) $mentor->id) {
            throw new BookingException('This office-hours session does not belong to the selected mentor.');
        }

        if ($session->status !== 'upcoming' || $session->is_full) {
            throw new BookingException('This office-hours session is no longer bookable.');
        }

        $sessionAt = Carbon::parse($session->session_date->toDateString().' '.$session->start_time, $session->timezone ?: config('app.timezone'));
        if ($sessionAt->lte(now())) {
            throw new BookingException('Please choose an upcoming office-hours session.');
        }

        $sessionAtUtc = $sessionAt->copy()->utc();

        if (
            Booking::query()
                ->where('office_hour_session_id', $session->id)
                ->where('student_id', $booker->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists()
        ) {
            throw new BookingException('You have already booked this office-hours session.');
        }

        if ((int) $session->current_occupancy >= (int) $session->max_spots) {
            throw new BookingException('This office-hours session is full.');
        }

        $session->current_occupancy = (int) $session->current_occupancy + 1;
        $session->is_full = $session->current_occupancy >= (int) $session->max_spots;
        if (! $session->first_booker_id) {
            $session->first_booker_id = $booker->id;
            $session->first_booked_at = now();
        }
        $session->service_locked = $session->current_occupancy >= 2;
        $session->save();

        return [
            $sessionAtUtc,
            $session->timezone ?: 'UTC',
            max((int) $service->duration_minutes, 1),
            null,
            $session->id,
        ];
    }

    private function assertBookerCanBookMentor(User $booker, Mentor $mentor): void
    {
        if ((int) ($mentor->user_id ?? 0) === (int) $booker->id) {
            throw new BookingException('You cannot book a meeting with yourself.');
        }
    }

    private function assertMentorOffersService(Mentor $mentor, ServiceConfig $service): void
    {
        $offersService = $mentor->services()
            ->where('services_config.id', $service->id)
            ->where('services_config.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $offersService) {
            throw new BookingException('This mentor does not currently offer the selected service.');
        }
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
            'currency' => $this->currency(),
            'price_1on1' => $service->price_1on1 !== null ? (float) $service->price_1on1 : null,
            'price_1on3_per_person' => $service->price_1on3_per_person !== null ? (float) $service->price_1on3_per_person : null,
            'price_1on3_total' => $service->price_1on3_total !== null ? (float) $service->price_1on3_total : null,
            'price_1on5_per_person' => $service->price_1on5_per_person !== null ? (float) $service->price_1on5_per_person : null,
            'price_1on5_total' => $service->price_1on5_total !== null ? (float) $service->price_1on5_total : null,
            'office_hours_subscription_price' => $service->office_hours_subscription_price !== null ? (float) $service->office_hours_subscription_price : null,
        ];
    }

    private function currency(): string
    {
        return strtoupper((string) config('app.currency', 'USD'));
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
