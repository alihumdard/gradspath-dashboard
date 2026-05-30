<?php

namespace App\Services;

class VerificationCodeResult
{
    private function __construct(
        public readonly bool $valid,
        public readonly string $message,
        public readonly string $type,
    ) {
    }

    public static function valid(): self
    {
        return new self(true, 'Your email address has been verified.', 'valid');
    }

    public static function invalid(): self
    {
        return new self(false, 'The verification code is incorrect. Please check the email and try again.', 'invalid');
    }

    public static function expired(): self
    {
        return new self(false, 'That verification code has expired. Request a new code and try again.', 'expired');
    }

    public static function locked(): self
    {
        return new self(false, 'Too many incorrect attempts. Request a new verification code to continue.', 'locked');
    }

    public static function missing(): self
    {
        return new self(false, 'Request a fresh verification code to continue.', 'missing');
    }
}
