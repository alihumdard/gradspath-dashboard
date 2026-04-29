<?php

namespace Modules\OfficeHours\app\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\ServiceConfig;

class OfficeHourSessionSyncService
{
    private const WEEKDAYS = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    ];

    public function syncUpcomingWeeklySessions(?Carbon $now = null): int
    {
        $now ??= now();
        $created = 0;

        OfficeHourSchedule::query()
            ->with(['mentor.services', 'sessions' => fn ($query) => $query->orderByDesc('session_date')->orderByDesc('start_time')])
            ->where('is_active', true)
            ->where('frequency', 'weekly')
            ->whereHas('mentor.services', fn ($query) => $query
                ->where('services_config.is_active', true)
                ->where('services_config.is_office_hours', true)
                ->where('mentor_services.is_active', true))
            ->chunkById(100, function (Collection $schedules) use ($now, &$created) {
                foreach ($schedules as $schedule) {
                    $created += $this->ensureNextSession($schedule, $now) ? 1 : 0;
                }
            });

        return $created;
    }

    public function syncTestingSessions(int $intervalMinutes = 2, ?Carbon $now = null): int
    {
        $now ??= now();
        $intervalMinutes = max($intervalMinutes, 1);
        $created = 0;

        OfficeHourSchedule::query()
            ->with(['mentor.services', 'sessions' => fn ($query) => $query->orderByDesc('session_date')->orderByDesc('start_time')])
            ->where('is_active', true)
            ->where('frequency', 'weekly')
            ->whereHas('mentor.services', fn ($query) => $query
                ->where('services_config.is_active', true)
                ->where('services_config.is_office_hours', true)
                ->where('mentor_services.is_active', true))
            ->chunkById(100, function (Collection $schedules) use ($now, $intervalMinutes, &$created) {
                foreach ($schedules as $schedule) {
                    $targetStart = $now->copy()
                        ->setTimezone($schedule->timezone ?: config('app.timezone', 'UTC'))
                        ->addMinutes($intervalMinutes)
                        ->second(0);

                    $created += $this->ensureNextSessionAt($schedule, $targetStart) ? 1 : 0;
                }
            });

        return $created;
    }

    private function ensureNextSession(OfficeHourSchedule $schedule, Carbon $now): bool
    {
        $targetStart = $this->nextStartForSchedule($schedule, $now);

        return $this->ensureNextSessionAt($schedule, $targetStart);
    }

    private function ensureNextSessionAt(OfficeHourSchedule $schedule, Carbon $targetStart): bool
    {
        $hasUpcoming = $schedule->sessions()
            ->where('status', 'upcoming')
            ->where(function ($query) use ($targetStart) {
                $query
                    ->whereDate('session_date', '>', $targetStart->toDateString())
                    ->orWhere(function ($query) use ($targetStart) {
                        $query
                            ->whereDate('session_date', $targetStart->toDateString())
                            ->where('start_time', '>=', $targetStart->format('H:i:s'));
                    });
            })
            ->exists();

        if ($hasUpcoming) {
            return false;
        }

        $service = $this->nextServiceForSchedule($schedule);

        if (! $service) {
            return false;
        }

        DB::transaction(function () use ($schedule, $service, $targetStart) {
            OfficeHourSession::query()->create([
                'schedule_id' => $schedule->id,
                'current_service_id' => $service->id,
                'session_date' => $targetStart->toDateString(),
                'start_time' => $targetStart->format('H:i:s'),
                'timezone' => $schedule->timezone ?: config('app.timezone', 'UTC'),
                'current_occupancy' => 0,
                'max_spots' => (int) ($schedule->max_spots ?: 3),
                'is_full' => false,
                'service_locked' => false,
                'service_choice_cutoff_at' => $targetStart->copy()->subHours(12)->utc(),
                'status' => 'upcoming',
            ]);

            $schedule->forceFill(['current_service_id' => $service->id])->save();
        });

        return true;
    }

    private function nextStartForSchedule(OfficeHourSchedule $schedule, Carbon $now): Carbon
    {
        $timezone = $schedule->timezone ?: config('app.timezone', 'UTC');
        $localNow = $now->copy()->setTimezone($timezone);
        $dayIndex = self::WEEKDAYS[(string) $schedule->day_of_week] ?? 0;
        $startTime = substr((string) $schedule->start_time, 0, 8);

        $target = $localNow->copy()->startOfDay();
        $daysUntil = ($dayIndex - (int) $target->dayOfWeek + 7) % 7;
        $target->addDays($daysUntil);

        [$hour, $minute, $second] = array_pad(array_map('intval', explode(':', $startTime)), 3, 0);
        $target->setTime($hour, $minute, $second);

        if ($target->lte($localNow)) {
            $target->addWeek();
        }

        return $target;
    }

    private function nextServiceForSchedule(OfficeHourSchedule $schedule): ?ServiceConfig
    {
        $services = $schedule->mentor?->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->wherePivot('is_active', true)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get(['services_config.id', 'services_config.service_name']) ?? collect();

        if ($services->isEmpty()) {
            return null;
        }

        $lastServiceId = $schedule->sessions()
            ->whereNotNull('current_service_id')
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->value('current_service_id') ?: $schedule->current_service_id;

        if (! $lastServiceId) {
            return $services->first();
        }

        $index = $services->values()->search(fn (ServiceConfig $service) => (int) $service->id === (int) $lastServiceId);

        if ($index === false) {
            return $services->first();
        }

        return $services->values()->get(($index + 1) % $services->count());
    }
}
