<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Events\CreditsPurchased;
use Modules\Payments\app\Models\CreditTransaction;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Models\UserCredit;

class CreditService
{
    public function getBalance(User $user): int
    {
        return (int) UserCredit::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        )->balance;
    }

    public function creditsNeeded(ServiceConfig $service, string $meetingSize): int
    {
        return match ($meetingSize) {
            '1on3' => (int) $service->credit_cost_1on3,
            '1on5' => (int) $service->credit_cost_1on5,
            default => (int) $service->credit_cost_1on1,
        };
    }

    public function deduct(User $user, int $amount, ?Booking $booking = null, ?string $description = null): UserCredit
    {
        return DB::transaction(function () use ($user, $amount, $booking, $description) {
            $wallet = UserCredit::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            if ($wallet->balance < $amount) {
                throw new \RuntimeException('Insufficient credits.');
            }

            $wallet->balance -= $amount;
            $wallet->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'booking_id' => $booking?->id,
                'type' => 'deduction',
                'amount' => -$amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'created_at' => now(),
            ]);

            return $wallet;
        });
    }

    public function purchase(User $user, int $amount, ?string $paymentId = null, ?string $eventId = null): UserCredit
    {
        return DB::transaction(function () use ($user, $amount, $paymentId, $eventId) {
            $wallet = UserCredit::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            $wallet->balance += $amount;
            $wallet->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'purchase',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'stripe_payment_id' => $paymentId,
                'stripe_event_id' => $eventId,
                'description' => 'Credit purchase',
                'created_at' => now(),
            ]);

            event(new CreditsPurchased($user, $amount));

            return $wallet;
        });
    }

    public function refund(User $user, int $amount, ?Booking $booking = null, ?User $performedBy = null, ?string $description = null): UserCredit
    {
        return DB::transaction(function () use ($user, $amount, $booking, $performedBy, $description) {
            $wallet = UserCredit::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            $wallet->balance += $amount;
            $wallet->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'booking_id' => $booking?->id,
                'type' => 'refund',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'performed_by' => $performedBy?->id,
                'description' => $description,
                'created_at' => now(),
            ]);

            return $wallet;
        });
    }
}
