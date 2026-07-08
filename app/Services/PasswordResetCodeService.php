<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\QueuedPasswordResetCode;
use Illuminate\Support\Facades\Hash;

class PasswordResetCodeService
{
    private const CODE_TTL_MINUTES = 30;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function send(User $user, bool $force = false): bool
    {
        $existing = PasswordResetCode::query()
            ->where('email', $user->email)
            ->first();

        if (
            !$force
            && $existing?->last_sent_at
            && $existing->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
        ) {
            return false;
        }

        $code = (string) random_int(100000, 999999);

        PasswordResetCode::query()->updateOrCreate(
            ['email' => $user->email],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'attempts' => 0,
                'last_sent_at' => now(),
                'verified' => false,
            ]
        );

        $user->notify(new QueuedPasswordResetCode($code, $user, self::CODE_TTL_MINUTES));

        return true;
    }

    public function verify(string $email, string $code): VerificationCodeResult
    {
        $normalizedCode = preg_replace('/\D+/', '', $code) ?? '';

        if (!preg_match('/^\d{6}$/', $normalizedCode)) {
            return VerificationCodeResult::invalid();
        }

        $record = PasswordResetCode::query()
            ->where('email', $email)
            ->first();

        if (!$record) {
            return VerificationCodeResult::missing();
        }

        if ($record->expires_at->isPast()) {
            return VerificationCodeResult::expired();
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return VerificationCodeResult::locked();
        }

        if (!Hash::check($normalizedCode, $record->code_hash)) {
            $record->increment('attempts');

            return VerificationCodeResult::invalid();
        }

        $record->update(['verified' => true]);

        return VerificationCodeResult::valid();
    }

    public function isVerified(string $email): bool
    {
        return PasswordResetCode::query()
            ->where('email', $email)
            ->where('verified', true)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function forget(string $email): void
    {
        PasswordResetCode::query()->where('email', $email)->delete();
    }
}
