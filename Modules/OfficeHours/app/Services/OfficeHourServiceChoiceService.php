<?php

namespace Modules\OfficeHours\app\Services;

use Carbon\Carbon;
use Modules\Auth\app\Models\User;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\ServiceConfig;

class OfficeHourServiceChoiceService
{
    public function payload(OfficeHourSession $session, User $user): array
    {
        $session->loadMissing(['schedule.mentor.services', 'currentService:id,service_name']);

        $services = $this->availableServices($session);
        $window = $this->window($session);
        $eligibility = $this->eligibility($session, $user);

        return [
            'eligible' => $eligibility['eligible'],
            'reason' => $eligibility['reason'],
            'windowOpensAt' => $window['opens_at']?->toIso8601String(),
            'windowClosesAt' => $window['closes_at']?->toIso8601String(),
            'changeUrl' => route('student.office-hours.sessions.service', $session->id),
            'currentServiceId' => $session->current_service_id ? (int) $session->current_service_id : null,
            'currentServiceName' => $session->currentService?->service_name ?? 'Office Hours',
            'availableServices' => $services->map(fn (ServiceConfig $service) => [
                'id' => (int) $service->id,
                'name' => $service->service_name,
            ])->values()->all(),
        ];
    }

    public function change(OfficeHourSession $session, User $user, int $serviceId): OfficeHourSession
    {
        $session->loadMissing(['schedule.mentor.services']);

        $eligibility = $this->eligibility($session, $user);

        if (! $eligibility['eligible']) {
            throw new \RuntimeException($eligibility['reason']);
        }

        $service = $this->availableServices($session)
            ->first(fn (ServiceConfig $service) => (int) $service->id === $serviceId);

        if (! $service) {
            throw new \RuntimeException('Choose an active service this mentor offers.');
        }

        $session->forceFill(['current_service_id' => $serviceId])->save();

        return $session->fresh(['schedule.mentor.services', 'currentService:id,service_name']);
    }

    public function eligibility(OfficeHourSession $session, User $user): array
    {
        $session->loadMissing('schedule');

        if ((int) ($session->first_booker_id ?? 0) !== (int) $user->id) {
            return $this->ineligible('Only the first student booked for this session can choose the focus.');
        }

        if ((int) $session->current_occupancy !== 1 || (bool) $session->service_locked) {
            return $this->ineligible('The office-hours focus is locked because another student has joined.');
        }

        if ((string) $session->status !== 'upcoming') {
            return $this->ineligible('This office-hours session is no longer upcoming.');
        }

        $startsAt = $this->startsAt($session);
        $now = now($startsAt->timezone);
        $windowOpensAt = $startsAt->copy()->subDay();
        $windowClosesAt = $this->window($session)['closes_at'];

        if ($now->lt($windowOpensAt)) {
            return $this->ineligible('Service choice opens 24 hours before the session starts.');
        }

        if (! $windowClosesAt || $now->gte($windowClosesAt)) {
            return $this->ineligible('Service choice closed 12 hours before the session starts.');
        }

        return ['eligible' => true, 'reason' => null];
    }

    public function availableServices(OfficeHourSession $session)
    {
        $mentor = $session->schedule?->mentor;

        if (! $mentor) {
            return collect();
        }

        return $mentor->services()
            ->where('services_config.is_active', true)
            ->where('services_config.is_office_hours', false)
            ->wherePivot('is_active', true)
            ->orderBy('mentor_services.sort_order')
            ->orderBy('services_config.sort_order')
            ->get(['services_config.id', 'services_config.service_name']);
    }

    private function window(OfficeHourSession $session): array
    {
        $startsAt = $this->startsAt($session);

        return [
            'opens_at' => $startsAt->copy()->subDay(),
            'closes_at' => $startsAt->copy()->subHours(12),
        ];
    }

    private function startsAt(OfficeHourSession $session): Carbon
    {
        return Carbon::parse(
            $session->session_date->toDateString().' '.$session->start_time,
            $session->timezone ?: config('app.timezone', 'UTC')
        );
    }

    private function ineligible(string $reason): array
    {
        return ['eligible' => false, 'reason' => $reason];
    }
}
