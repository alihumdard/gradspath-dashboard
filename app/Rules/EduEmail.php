<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EduEmail implements ValidationRule
{
    public function __construct(
        private readonly ?string $email = null,
        private readonly string $message = 'The email address must use a .edu domain.',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('local')) {
            return;
        }

        $email = $this->email ?? (is_string($value) ? $value : null);

        if ($email === null || $email === '') {
            return;
        }

        if (! self::matches($email)) {
            $fail($this->message);
        }
    }

    public static function matches(?string $email): bool
    {
        if (! is_string($email) || ! str_contains($email, '@')) {
            return false;
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1) ?: '');

        return str_ends_with($domain, '.edu') || str_contains($domain, '.edu.');
    }
}
