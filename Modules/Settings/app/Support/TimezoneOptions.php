<?php

namespace Modules\Settings\app\Support;

use Modules\Auth\app\Models\User;

class TimezoneOptions
{
    public const FALLBACK = 'Asia/Karachi';

    public static function all(): array
    {
        $timezones = timezone_identifiers_list();

        usort($timezones, function (string $a, string $b): int {
            if ($a === self::FALLBACK) {
                return -1;
            }

            if ($b === self::FALLBACK) {
                return 1;
            }

            return $a <=> $b;
        });

        return collect($timezones)
            ->mapWithKeys(fn (string $timezone): array => [$timezone => self::labelFor($timezone)])
            ->all();
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
        if (self::isRecognized($explicit)) {
            return (string) $explicit;
        }

        $savedTimezone = $user?->setting?->timezone;

        if (self::isRecognized($savedTimezone)) {
            return (string) $savedTimezone;
        }

        return self::fallback();
    }

    private static function isRecognized(?string $timezone): bool
    {
        return is_string($timezone)
            && in_array($timezone, timezone_identifiers_list(), true);
    }

    private static function labelFor(string $timezone): string
    {
        $parts = explode('/', $timezone);
        $city = str_replace('_', ' ', end($parts) ?: $timezone);
        $region = count($parts) > 1 ? $parts[0] : 'Other';

        return "{$city} - {$region}";
    }
}
