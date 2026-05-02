<?php

namespace Modules\MentorNotes\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\MentorNotes\app\Models\MentorNote;

class MentorNotesController extends Controller
{
    public function index(): View
    {
        $student = Auth::user();

        $notes = MentorNote::query()
            ->with([
                'student:id,name,email,avatar_url',
                'mentor:id,user_id',
                'mentor.user:id,name,email,avatar_url',
                'booking:id,service_config_id,session_at,session_timezone,session_type',
                'booking.service:id,service_name,service_slug',
            ])
            ->where('student_id', $student?->id)
            ->where('is_deleted', false)
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get();

        $studentPayload = $student && $notes->isNotEmpty()
            ? $this->transformMentors($notes, $student)
            : [];

        return view('mentor-notes::student.index', [
            'mentorNotesPageData' => [
                'hasDynamicData' => true,
                'viewerRole' => 'student',
                'viewerStudentId' => (int) ($student?->id ?? 0),
                'users' => $studentPayload,
            ],
        ]);
    }

    private function transformMentors(Collection $notes, object $student): array
    {
        return $notes
            ->groupBy('mentor_id')
            ->map(function (Collection $group, int|string $mentorId) use ($student): ?array {
                /** @var MentorNote|null $first */
                $first = $group->first();
                $mentorUser = $first?->mentor?->user;

                if (! $first || ! $mentorUser) {
                    return null;
                }

                $sortedNotes = $group
                    ->sortByDesc(fn (MentorNote $note) => optional($note->session_date)?->format('Y-m-d') ?: '')
                    ->values();

                return [
                    'id' => (int) $mentorId,
                    'name' => $mentorUser->name ?? 'Mentor',
                    'email' => $mentorUser->email ?? '',
                    'avatarUrl' => $mentorUser->avatar_url,
                    'studentName' => $student->name ?? 'Student',
                    'studentEmail' => $student->email ?? '',
                    'studentAvatarUrl' => $student->avatar_url,
                    'sessions' => $sortedNotes->count(),
                    'notes' => $sortedNotes
                        ->map(fn (MentorNote $note) => $this->notePayload($note))
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function notePayload(MentorNote $note): array
    {
        $mentorUser = $note->mentor?->user;

        return [
            'id' => (int) $note->id,
            'mentor' => $mentorUser?->name ?? 'Mentor',
            'mentorEmail' => $mentorUser?->email ?? 'Not available',
            'service' => $note->service_type ?: $note->booking?->service?->service_name ?: 'Session',
            'date' => optional($note->session_date)?->format('F j, Y') ?? 'Unknown date',
            'rawDate' => optional($note->session_date)?->format('Y-m-d'),
            'sessionWork' => (string) $note->worked_on,
            'nextSteps' => (string) $note->next_steps,
            'sessionOutcome' => (string) $note->session_result,
            'sessionReflection' => (string) $note->strengths_challenges,
            'otherNotes' => (string) $note->other_notes,
        ];
    }
}
