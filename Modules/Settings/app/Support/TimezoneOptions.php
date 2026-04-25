<?php

namespace Modules\Settings\app\Support;

use Modules\Auth\app\Models\User;

class TimezoneOptions
{
    public const FALLBACK = 'UTC';

    public static function all(): array
    {
        return [
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time',
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
            'Europe/London' => 'London',
            'Asia/Karachi' => 'Karachi',
            'UTC' => 'UTC',
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
