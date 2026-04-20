<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\app\Models\Mentor;

class MentorDiscoveryService
{
    public function search(array $filters): LengthAwarePaginator
    {
        return Mentor::query()
            ->with(['user:id,name,email,avatar_url', 'university:id,name,display_name', 'rating'])
            ->where('status', 'active')
            ->whereHas('user')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('bio', 'like', "%{$q}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['mentor_type'] ?? null, fn($q, $type) => $q->where('mentor_type', $type))
            ->when($filters['program_type'] ?? null, fn($q, $type) => $q->where('program_type', $type))
            ->when($filters['university_id'] ?? null, fn($q, $id) => $q->where('university_id', $id))
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function browseData(string $portal = 'student', ?int $viewerMentorId = null): Collection
    {
        return Mentor::query()
            ->with(['user:id,name,email,avatar_url', 'university:id,name,display_name', 'rating', 'services'])
            ->where('status', 'active')
            ->whereHas('user')
            ->get()
            ->map(function (Mentor $mentor) use ($viewerMentorId, $portal): array {
                $name = $mentor->user?->name ?? 'Mentor';
                $school = $mentor->university?->display_name ?: $mentor->university?->name ?: 'University';
                $services = $mentor->services
                    ->pluck('service_name')
                    ->filter(fn($service) => is_string($service) && $service !== '')
                    ->values();

                return [
                    'id' => $mentor->id,
                    'type' => $mentor->mentor_type === 'professional' ? 'professionals' : 'graduates',
                    'name' => $name,
                    'initials' => $this->initials($name),
                    'category' => $this->programFamily($mentor->program_type),
                    'categoryLabel' => $mentor->title ?: $this->programLabel($mentor->program_type),
                    'school' => $school,
                    'rating' => $mentor->rating?->avg_stars ? (float) $mentor->rating->avg_stars : null,
                    'officeHours' => $mentor->office_hours_schedule ?: 'Schedule coming soon',
                    'bio' => $mentor->bio ?: $mentor->description ?: 'Available to support students with applications, strategy, and next steps.',
                    'bioExtra' => $mentor->description ?: $mentor->bio ?: 'Mentor profile details coming soon.',
                    'services' => $services->isNotEmpty()
                        ? $services->all()
                        : ['Office Hours', 'Program Insights', 'Application Review'],
                    'reviewShort' => $mentor->rating?->top_tag
                        ?: 'Students value this mentor for practical, focused guidance.',
                    'reviewExtra' => collect($mentor->rating?->top_tags_json ?? [])
                        ->filter(fn($tag) => is_string($tag) && $tag !== '')
                        ->take(3)
                        ->implode(' • '),
                    'canBook' => $viewerMentorId === null || (int) $mentor->id !== (int) $viewerMentorId,
                    'bookingUrl' => route("{$portal}.mentor.book", $mentor->id),
                ];
            })
            ->sortByDesc(fn(array $mentor) => $mentor['rating'] ?? 0)
            ->values();
    }

    public function featured(int $limit = 6, string $portal = 'student', ?int $viewerMentorId = null)
    {
        return Mentor::query()
            ->with(['user:id,name,avatar_url', 'university:id,name,display_name', 'rating', 'services'])
            ->where('status', 'active')
            ->whereHas('user')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortByDesc(fn(Mentor $mentor) => $mentor->rating?->avg_stars ?? 0)
            ->values()
            ->map(fn(Mentor $mentor): array => $this->mapMentorCard($mentor, $portal, $viewerMentorId))
            ->values();
    }

    private function mapMentorCard(Mentor $mentor, string $portal = 'student', ?int $viewerMentorId = null): array
    {
        $name = $mentor->user?->name ?? 'Mentor';
        $school = $mentor->university?->display_name ?: $mentor->university?->name ?: 'University';
        $services = $mentor->services
            ->pluck('service_name')
            ->filter(fn($service) => is_string($service) && $service !== '')
            ->values();
        $canBook = $portal !== 'mentor' || $viewerMentorId === null || (int) $mentor->id !== (int) $viewerMentorId;

        return [
            'id' => $mentor->id,
            'name' => $name,
            'initials' => $this->initials($name),
            'role' => ($mentor->title ?: $this->programLabel($mentor->program_type)).' • '.$school,
            'rating' => $mentor->rating?->avg_stars
                ? number_format((float) $mentor->rating->avg_stars, 1)
                : 'New',
            'officeHours' => $mentor->office_hours_schedule ?: 'Schedule coming soon',
            'bio' => $mentor->bio ?: $mentor->description ?: 'Available to support students with applications, strategy, and next steps.',
            'services' => $services->isNotEmpty()
                ? $services->take(6)->all()
                : ['Office Hours', 'Program Insights', 'Application Review'],
            'review' => $mentor->rating?->top_tag
                ?: 'Students value this mentor for practical, focused guidance.',
            'canBook' => $canBook,
            'bookingUrl' => $canBook ? route("{$portal}.mentor.book", $mentor->id) : null,
        ];
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    private function programFamily(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'mba',
            'law' => 'law',
            'cmhc', 'mft', 'msw', 'clinical_psy', 'therapy' => 'therapy',
            default => 'other',
        };
    }

    private function programLabel(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'MBA',
            'law' => 'Law',
            'cmhc' => 'Counseling',
            'mft' => 'Marriage & Family Therapy',
            'msw' => 'Social Work',
            'clinical_psy' => 'Clinical Psychology',
            'therapy' => 'Therapy',
            'other', null, '' => 'General Mentor',
            default => ucfirst(str_replace('_', ' ', (string) $programType)),
        };
    }
}
