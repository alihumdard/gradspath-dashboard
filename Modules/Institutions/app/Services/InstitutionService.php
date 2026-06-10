<?php

namespace Modules\Institutions\app\Services;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Institutions\app\Models\University;

class InstitutionService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return University::query()
            ->where('is_active', true)
            ->when(
                $filters['tier'] ?? null,
                fn($q, $tier) => $q->whereHas(
                    'programs',
                    fn($programs) => $programs
                        ->where('is_active', true)
                        ->where('tier', $tier)
                )
            )
            ->when($filters['q'] ?? null, fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function browseData(string $portal = 'student', ?int $viewerMentorId = null): Collection
    {
        return University::query()
            ->where('is_active', true)
            ->with([
                'programs' => fn($q) => $q
                    ->where('is_active', true)
                    ->orderBy('program_name'),
                'mentors' => fn($q) => $q
                    ->where('status', 'active')
                    ->with([
                        'user:id,name,avatar_url',
                        'rating',
                        'services:id,service_name',
                        'latestVisibleFeedback:feedback.id,feedback.mentor_id,feedback.comment,feedback.created_at',
                    ]),
            ])
            ->orderByRaw('COALESCE(display_name, name)')
            ->get()
            ->map(function (University $university) use ($portal, $viewerMentorId): array {
                $programs = $university->programs
                    ->map(function ($program) use ($university, $portal, $viewerMentorId): array {
                        $matchingMentors = $university->mentors
                            ->filter(fn($mentor) => $mentor->program_type === $program->program_type)
                            ->values();

                        return [
                            'name' => $program->program_name,
                            'type' => $program->program_type,
                            'family' => $this->programFamily($program->program_type),
                            'tier' => $this->tierLabel($program->tier),
                            'tierLabel' => $this->tierBadgeLabel($program->tier),
                            'description' => $program->description,
                            'mentors' => $matchingMentors
                                ->map(function ($mentor) use ($university, $portal, $viewerMentorId): array {
                                    $name = $mentor->user?->name ?? 'Mentor';
                                    $services = $mentor->services
                                        ->pluck('service_name')
                                        ->filter(fn($service) => is_string($service) && $service !== '')
                                        ->take(6)
                                        ->values();
                                    $canBook = $portal !== 'mentor' || $viewerMentorId === null || (int) $mentor->id !== (int) $viewerMentorId;

                                    return [
                                        'id' => $mentor->id,
                                        'name' => $mentor->user?->name ?? 'Mentor',
                                        'avatarUrl' => $mentor->user?->avatar_url ?: $mentor->avatar_url,
                                        'initials' => $this->initials($name),
                                        'roleLabel' => $this->mentorRoleLabel($mentor->program_type, $university),
                                        'score' => $mentor->rating?->has_effective_rating
                                            ? number_format((float) $mentor->rating->effective_rating, 1)
                                            : 'New',
                                        'officeHours' => $mentor->office_hours_schedule ?: 'Schedule coming soon',
                                        'description' => $mentor->description
                                            ?: $mentor->bio
                                            ?: 'Available to support applications, strategy, and next steps for this program.',
                                        'services' => $services->isNotEmpty()
                                            ? $services->all()
                                            : ['Office Hours', 'Program Insights', 'Application Review'],
                                        'review' => $mentor->latestVisibleFeedback?->comment
                                            ?: $mentor->rating?->top_tag
                                            ?: 'Students value this mentor for practical, focused guidance.',
                                        'feedbackUrl' => route('feedback.index', [
                                            'mentor_id' => $mentor->id,
                                            'mentor_type' => $mentor->mentor_type === 'professional' ? 'professional' : 'graduate',
                                            'program' => $this->programFamily($mentor->program_type),
                                        ]),
                                        'canBook' => $canBook,
                                        'bookingUrl' => $canBook ? route("{$portal}.mentor.book", $mentor->id) : null,
                                        'tags' => collect($mentor->rating?->top_tags_json ?? [])
                                            ->filter(fn($tag) => is_string($tag) && $tag !== '')
                                            ->take(3)
                                            ->values()
                                            ->all(),
                                        'icon' => $this->programIcon($mentor->program_type),
                                    ];
                                })
                                ->all(),
                        ];
                    })
                    ->values();

                return [
                    'id' => $university->id,
                    'school' => $university->display_name ?: $university->name,
                    'fullName' => $university->name,
                    'logo_url' => $university->logo_url,
                    'programs' => $programs->all(),
                ];
            });
    }

    public function detail(int $id): University
    {
        return University::query()
            ->with(['programs' => fn($q) => $q->where('is_active', true), 'mentors.user:id,name,avatar_url'])
            ->findOrFail($id);
    }

    private function programFamily(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'MBA',
            'law' => 'Law',
            default => 'Therapy',
        };
    }

    private function tierLabel(?string $tier): string
    {
        return match ($tier) {
            'elite' => 'Elite Programs',
            'top' => 'Top 25 Programs',
            'regional' => 'Regional Programs',
            default => 'Programs',
        };
    }

    private function tierBadgeLabel(?string $tier): string
    {
        return match ($tier) {
            'elite' => 'Elite Programs',
            'top' => 'Top 25 Programs',
            'regional' => 'Regional Programs',
            default => 'Programs',
        };
    }

    private function programIcon(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'briefcase-business',
            'law' => 'scale',
            default => 'heart-handshake',
        };
    }

    private function mentorRoleLabel(?string $programType, University $university): string
    {
        return $this->programFamily($programType).' • '.($university->display_name ?: $university->name);
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'GP';
    }
}
