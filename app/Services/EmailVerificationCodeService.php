<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCodeService
{
    private const CODE_TTL_MINUTES = 30;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function send(User $user, bool $force = false): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $existing = EmailVerificationCode::query()
            ->where('user_id', $user->getKey())
            ->first();

        if (
            !$force
            && $existing?->last_sent_at
            && $existing->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
        ) {
            return false;
        }

        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'attempts' => 0,
                'last_sent_at' => now(),
            ]
        );

        $user->notify(new QueuedVerifyEmail($code, $user, self::CODE_TTL_MINUTES));

        return true;
    }

    public function verify(User $user, string $code): VerificationCodeResult
    {
        $normalizedCode = preg_replace('/\D+/', '', $code) ?? '';

        if (!preg_match('/^\d{6}$/', $normalizedCode)) {
            return VerificationCodeResult::invalid();
        }

        $record = EmailVerificationCode::query()
            ->where('user_id', $user->getKey())
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

        $record->delete();

        return VerificationCodeResult::valid();
    }
}
