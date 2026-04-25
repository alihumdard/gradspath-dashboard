<?php

namespace Modules\Feedback\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Feedback\app\Http\Requests\StoreFeedbackRequest;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Services\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedback) {}

    public function index(Request $request): View
    {
        $items = Feedback::query()
            ->with([
                'student:id,name',
                'booking:id,service_config_id,session_at,session_timezone',
                'booking.service:id,service_name,service_slug',
                'mentor:id,user_id,title,grad_school_display,mentor_type,program_type,bio,description',
                'mentor.user:id,name',
                'mentor.services:id,service_name',
                'mentor.rating:id,mentor_id,avg_stars,recommend_rate,total_reviews,total_sessions,top_tag,top_tags_json',
            ])
            ->where('is_visible', true)
            ->orderByDesc('created_at')
            ->get();

        $mentorData = $items
            ->groupBy('mentor_id')
            ->map(fn (Collection $group) => $this->transformMentor($group))
            ->filter()
            ->values()
            ->all();

        $summary = $this->summary($items);

        return view('feedback::student.index', [
            'feedbackItems' => $items,
            'feedbackPageData' => [
                'hasDynamicData' => true,
                'mentors' => $mentorData,
                'summary' => $summary,
            ],
            'feedbackSummary' => $summary,
        ]);
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        try {
            $this->feedback->storeStudentFeedback(Auth::user(), $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['feedback' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Feedback submitted successfully.');
    }

    private function transformMentor(Collection $group): ?array
    {
        /** @var Feedback|null $first */
        $first = $group->first();
        $mentor = $first?->mentor;
        $user = $mentor?->user;

        if (! $first || ! $mentor || ! $user) {
            return null;
        }

        $services = $mentor->services
            ->pluck('service_name')
            ->filter()
            ->unique()
            ->values();

        if ($services->isEmpty()) {
            $services = $group
                ->map(fn (Feedback $feedback) => $this->serviceName($feedback))
                ->filter()
                ->unique()
                ->values();
        }

        $reviews = $group
            ->sortByDesc(fn (Feedback $feedback) => $feedback->booking?->session_at ?? $feedback->created_at)
            ->values()
            ->map(function (Feedback $feedback): array {
                return [
                    'student' => $feedback->student?->name ?? 'Student',
                    'date' => optional($feedback->booking?->session_at ?? $feedback->created_at)?->format('F j, Y'),
                    'serviceUsed' => $this->serviceName($feedback) ?? 'Session',
                    'meetingRating' => (int) $feedback->stars,
                    'mentorKnowledge' => (string) ($feedback->preparedness_rating ?? ''),
                    'recommendation' => $feedback->recommend ? 'Yes' : 'No',
                    'quickFeedback' => (string) $feedback->comment,
                ];
            })
            ->all();

        $rating = $mentor->rating?->avg_stars !== null
            ? (float) $mentor->rating->avg_stars
            : round((float) $group->avg('stars'), 1);

        return [
            'id' => (int) $mentor->id,
            'name' => $user->name,
            'initials' => $this->initials($user->name),
            'category' => $this->categoryKey($mentor->program_type),
            'categoryLabel' => $this->categoryLabel($mentor->program_type),
            'profession' => (string) ($mentor->mentor_type ?: 'graduate'),
            'school' => (string) ($mentor->grad_school_display ?: 'Grads Paths'),
            'degree' => (string) ($mentor->title ?: $this->categoryLabel($mentor->program_type) ?: 'Mentor'),
            'rating' => $rating,
            'reviews' => (int) ($mentor->rating?->total_reviews ?? $group->count()),
            'sessions' => (int) ($mentor->rating?->total_sessions ?? $group->count()),
            'officeHours' => 'Available by booking',
            'description' => (string) ($mentor->description ?: $mentor->bio ?: 'Students share their session experiences and what made this mentor helpful.'),
            'services' => $services->all(),
            'reviewList' => $reviews,
        ];
    }

    private function summary(Collection $items): array
    {
        $total = $items->count();
        $averageRating = $total > 0 ? round((float) $items->avg('stars'), 1) : 0.0;
        $recommendRate = $total > 0 ? round(((int) $items->where('recommend', true)->count() / $total) * 100) : 0;

        $serviceCounts = $items
            ->map(fn (Feedback $feedback) => $this->serviceName($feedback))
            ->filter()
            ->countBy()
            ->sortDesc();

        $topMentioned = $serviceCounts->keys()->first() ?? 'No feedback yet';
        $otherMentions = $serviceCounts->keys()->slice(1, 2)->values()->all();

        return [
            'averageRating' => $averageRating,
            'completedSessions' => $total,
            'recommendRate' => $recommendRate,
            'topMentioned' => $topMentioned,
            'topMentionedOthers' => $otherMentions,
        ];
    }

    private function serviceName(Feedback $feedback): ?string
    {
        return $feedback->booking?->service?->service_name
            ?: $this->humanizeService($feedback->service_type);
    }

    private function categoryKey(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'mba',
            'law' => 'law',
            'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy' => 'therapy',
            default => 'mba',
        };
    }

    private function categoryLabel(?string $programType): string
    {
        return match ($programType) {
            'mba' => 'MBA',
            'law' => 'Law',
            'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy' => 'Therapy',
            default => 'General',
        };
    }

    private function humanizeService(?string $service): ?string
    {
        if (! is_string($service) || trim($service) === '') {
            return null;
        }

        return Str::of($service)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function initials(?string $name): string
    {
        return Str::of((string) $name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
