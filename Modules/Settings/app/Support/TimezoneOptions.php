<?php

namespace Modules\Settings\app\Support;

use Modules\Auth\app\Models\User;

class TimezoneOptions
{
    public const FALLBACK = 'Asia/Karachi';

    public static function all(): array
    {
        return [
            'Asia/Karachi' => 'Karachi',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $timezone): bool
    {
        return is_string($timezone) && in_array($timezone, self::values(), true);
    }

    public static function fallback(): string
    {
        return self::FALLBACK;
    }

    public static function preferredFor(?User $user, ?string $explicit = null): string
    {
        if (self::isSupported($explicit)) {
            return (string) $explicit;
        }

        $savedTimezone = $user?->setting?->timezone;

        if (self::isSupported($savedTimezone)) {
            return (string) $savedTimezone;
        }

        return self::fallback();
    }
}
