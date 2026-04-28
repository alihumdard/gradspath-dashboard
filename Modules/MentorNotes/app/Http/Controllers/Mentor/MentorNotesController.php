<?php

namespace Modules\MentorNotes\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Bookings\app\Models\Booking;
use Modules\MentorNotes\app\Http\Requests\StoreMentorNoteRequest;
use Modules\MentorNotes\app\Models\MentorNote;
use Modules\Settings\app\Models\Mentor;

class MentorNotesController extends Controller
{
    public function index(): View
    {
        $viewerMentor = $this->currentMentor();

        $notes = MentorNote::query()
            ->with([
                'student:id,name,email',
                'mentor:id,user_id',
                'mentor.user:id,name,email',
                'booking:id,service_config_id,session_at,session_timezone,session_type',
                'booking.service:id,service_name,service_slug',
            ])
            ->where('is_deleted', false)
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get();

        return view('mentor-notes::mentor.index', [
            'mentorNotesPageData' => [
                'hasDynamicData' => true,
                'viewerMentorId' => (int) $viewerMentor->id,
                'users' => $this->transformUsers($notes),
            ],
        ]);
    }

    public function edit(int $bookingId): View
    {
        $mentor = $this->currentMentor();
        $booking = $this->resolveHostedBooking($mentor, $bookingId);
        $this->ensureMentorNotesAllowed($booking);
        $note = $this->findMentorBookingNote($mentor, $booking);

        return view('mentor-notes::mentor.notes', [
            'booking' => $booking,
            'note' => $note,
            'mentorNotesFormData' => [
                'bookingId' => (int) $booking->id,
                'formAction' => route('mentor.notes.bookings.store', $booking->id),
                'noteExists' => $note !== null,
                'submitLabel' => $note ? 'Update Mentors Notes' : 'Submit Mentors Notes',
                'success' => session()->has('success'),
                'session' => $this->sessionPayload($booking),
            ],
        ]);
    }

    public function store(StoreMentorNoteRequest $request, int $bookingId): RedirectResponse
    {
        $mentor = $this->currentMentor();
        $booking = $this->resolveHostedBooking($mentor, $bookingId);
        $this->ensureMentorNotesAllowed($booking);

        $note = MentorNote::query()->updateOrCreate(
            [
                'mentor_id' => $mentor->id,
                'booking_id' => $booking->id,
            ],
            [
                'student_id' => $booking->student_id,
                'session_date' => $this->sessionDateForStorage($booking),
                'service_type' => $this->serviceName($booking),
                'worked_on' => $request->string('worked_on')->toString(),
                'next_steps' => $request->string('next_steps')->toString(),
                'session_result' => $request->string('session_result')->toString(),
                'strengths_challenges' => $request->string('strengths_challenges')->toString(),
                'other_notes' => $request->string('other_notes')->toString(),
                'is_deleted' => false,
                'deleted_by' => null,
                'deleted_at' => null,
            ],
        );

        $booking->forceFill([
            'mentor_feedback_done' => true,
        ])->save();

        return redirect()
            ->route('mentor.notes.bookings.edit', $booking->id)
            ->with('success', $note->wasRecentlyCreated ? 'Mentor notes saved successfully.' : 'Mentor notes updated successfully.');
    }

    private function currentMentor(): Mentor
    {
        return Mentor::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    private function resolveHostedBooking(Mentor $mentor, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->with([
                'booker:id,name,email',
                'mentor.user:id,name,email',
                'service:id,service_name,service_slug',
            ])
            ->findOrFail($bookingId);

        abort_unless((int) $booking->mentor_id === (int) $mentor->id, 403);

        return $booking;
    }

    private function findMentorBookingNote(Mentor $mentor, Booking $booking): ?MentorNote
    {
        return MentorNote::query()
            ->where('mentor_id', $mentor->id)
            ->where('booking_id', $booking->id)
            ->where('is_deleted', false)
            ->first();
    }

    private function ensureMentorNotesAllowed(Booking $booking): void
    {
        if ($booking->mentorNotesAllowed()) {
            return;
        }

        throw new HttpResponseException(
            redirect()
                ->route('mentor.bookings.show', $booking->id)
                ->with('error', $booking->mentorNotesMessage())
        );
    }

    private function transformUsers(Collection $notes): array
    {
        return $notes
            ->groupBy('student_id')
            ->map(function (Collection $group, int|string $studentId): ?array {
                /** @var MentorNote|null $first */
                $first = $group->first();
                $student = $first?->student;

                if (! $first || ! $student) {
                    return null;
                }

                $sortedNotes = $group
                    ->sortByDesc(fn (MentorNote $note) => optional($note->session_date)?->format('Y-m-d') ?: '')
                    ->values();

                return [
                    'id' => (int) $studentId,
                    'name' => $student->name ?? 'Student',
                    'email' => $student->email ?? '',
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
            'service' => $note->service_type ?: $this->serviceName($note->booking),
            'date' => optional($note->session_date)?->format('F j, Y') ?? 'Unknown date',
            'rawDate' => optional($note->session_date)?->format('Y-m-d'),
            'sessionWork' => (string) $note->worked_on,
            'nextSteps' => (string) $note->next_steps,
            'sessionOutcome' => (string) $note->session_result,
            'sessionReflection' => (string) $note->strengths_challenges,
            'otherNotes' => (string) $note->other_notes,
        ];
    }

    private function sessionPayload(Booking $booking): array
    {
        return [
            'fullName' => $booking->booker?->name ?? 'User Name',
            'email' => $booking->booker?->email ?? '',
            'mentorName' => $booking->mentor?->user?->name ?? 'Mentor Name',
            'mentorEmail' => $booking->mentor?->user?->email ?? '',
            'sessionDate' => $this->formattedSessionDate($booking),
            'sessionType' => $this->serviceName($booking),
        ];
    }

    private function formattedSessionDate(Booking $booking): string
    {
        $sessionAt = $booking->sessionAtInTimezone();

        return $sessionAt?->format('F j, Y') ?? optional($booking->session_at)?->format('F j, Y') ?? 'Not scheduled';
    }

    private function sessionDateForStorage(Booking $booking): string
    {
        $sessionAt = $booking->sessionAtInTimezone();

        return $sessionAt?->toDateString() ?? optional($booking->session_at)?->toDateString() ?? now()->toDateString();
    }

    private function serviceName(?Booking $booking): string
    {
        if ($booking?->service?->service_name) {
            return $booking->service->service_name;
        }

        return Str::of((string) ($booking?->session_type ?: 'Session'))
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }
}
