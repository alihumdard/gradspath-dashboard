<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingPresenter;
use Modules\Bookings\app\Services\ZoomService;
use Modules\Settings\app\Models\Mentor;

class BookingsController extends Controller
{
    public function __construct(
        private readonly BookingMeetingPresenter $meetingPresenter,
        private readonly ZoomService $zoom,
    ) {}

    public function index(Request $request): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();
        [$hostedBookings, $bookedBookings] = $this->bookingCollections($mentor);
        $selectedBooking = $this->defaultSelectedBooking($hostedBookings, $bookedBookings);

        return view('bookings::mentor.index', [
            'bookings' => $hostedBookings,
            'selectedBooking' => $selectedBooking,
            'bookingPageData' => $this->buildBookingDetailsPayload($mentor, $hostedBookings, $bookedBookings, $selectedBooking),
        ]);
    }

    public function show(int $id): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();
        $booking = Booking::query()
            ->with(['booker:id,name,email,avatar_url', 'mentor.user:id,name,email,avatar_url', 'service', 'officeHourSession', 'mentorNotes:id,booking_id,mentor_id,is_deleted'])
            ->findOrFail($id);
        Gate::authorize('view', $booking);

        [$hostedBookings, $bookedBookings] = $this->bookingCollections($mentor);

        return view('bookings::mentor.index', [
            'bookings' => $hostedBookings,
            'selectedBooking' => $booking,
            'bookingPageData' => $this->buildBookingDetailsPayload($mentor, $hostedBookings, $bookedBookings, $booking),
        ]);
    }

    public function startMeeting(int $id): RedirectResponse
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();
        $booking = Booking::query()->with('officeHourSession')->findOrFail($id);

        if ((int) $booking->mentor_id !== (int) $mentor->id) {
            Log::warning('Blocked non-host mentor from starting Zoom meeting.', [
                'booking_id' => $booking->id,
                'auth_user_id' => Auth::id(),
                'auth_mentor_id' => $mentor->id,
                'booking_mentor_id' => $booking->mentor_id,
            ]);

            abort(403);
        }

        if (! $this->isSyncedZoomBooking($booking)) {
            Log::warning('Mentor Zoom start route rejected unsynced booking.', [
                'booking_id' => $booking->id,
                'calendar_provider' => $this->calendarProvider($booking),
                'calendar_sync_status' => $this->calendarSyncStatus($booking),
                'external_calendar_event_id' => $this->externalMeetingId($booking),
            ]);

            return back()->with('error', 'Zoom meeting is not ready yet.');
        }

        if (! $booking->meetingAccessAllowed()) {
            Log::info('Mentor Zoom start route rejected before scheduled start time.', [
                'booking_id' => $booking->id,
                'meeting_id' => $this->externalMeetingId($booking),
                'session_at' => optional($booking->session_at)->toIso8601String(),
            ]);

            return redirect()
                ->route('mentor.bookings.show', $booking->id)
                ->with('status', $booking->meetingAccessMessage());
        }

        if (! $this->zoom->isConfigured()) {
            Log::warning('Mentor Zoom start route rejected because Zoom is not configured.', [
                'booking_id' => $booking->id,
                'meeting_id' => $this->externalMeetingId($booking),
            ]);

            return back()->with('error', 'Zoom booking is not configured right now.');
        }

        if (! $this->zoom->hasConnectedMentor($booking->mentor)) {
            Log::warning('Mentor Zoom start route rejected because mentor Zoom is not connected.', [
                'booking_id' => $booking->id,
                'meeting_id' => $this->externalMeetingId($booking),
                'mentor_id' => $booking->mentor_id,
            ]);

            return back()->with('error', 'Please reconnect Zoom to start this meeting.');
        }

        try {
            $meeting = $this->zoom->getMeeting($this->bookingWithSharedOfficeHoursMeeting($booking));
            $startUrl = trim((string) data_get($meeting, 'start_url', ''));

            Log::info('Mentor Zoom start route fetched meeting details.', [
                'booking_id' => $booking->id,
                'meeting_id' => $this->externalMeetingId($booking),
                'start_url_present' => $startUrl !== '',
                'join_url_present' => filled(data_get($meeting, 'join_url')),
            ]);

            if ($startUrl === '') {
                return back()->with('error', 'Zoom did not return a host start link.');
            }

            return redirect()->away($startUrl);
        } catch (\Throwable $exception) {
            Log::warning('Mentor Zoom start route failed.', [
                'booking_id' => $booking->id,
                'meeting_id' => $this->externalMeetingId($booking),
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Unable to start Zoom meeting right now.');
        }
    }

    private function buildBookingDetailsPayload(Mentor $mentor, Collection $hostedBookings, Collection $bookedBookings, ?Booking $selectedBooking): array
    {
        $serviceCatalog = [
            ['name' => 'Tutoring', 'slug' => 'tutoring'],
            ['name' => 'Program Insights', 'slug' => 'program_insights'],
            ['name' => 'Interview Prep', 'slug' => 'interview_prep'],
            ['name' => 'Application Review', 'slug' => 'application_review'],
            ['name' => 'Gap Year Planning', 'slug' => 'gap_year_planning'],
            ['name' => 'Office Hours', 'slug' => 'office_hours'],
            ['name' => 'Free Consultation', 'slug' => 'free_consultation'],
        ];

        $hosted = $hostedBookings
            ->sortBy('session_at')
            ->map(fn (Booking $booking) => $this->transformBooking($booking, 'hosted'))
            ->values()
            ->all();
        $booked = $bookedBookings
            ->sortBy('session_at')
            ->map(fn (Booking $booking) => $this->transformBooking($booking, 'booked'))
            ->values()
            ->all();

        return [
            'selectedBookingId' => $selectedBooking?->id,
            'selectedBooking' => $selectedBooking
                ? $this->transformBooking($selectedBooking, $this->bookingPerspective($selectedBooking, $mentor))
                : null,
            'serviceCatalog' => $serviceCatalog,
            'counterpartLabel' => 'Counterpart',
            'viewerRoleLabel' => 'You',
            'viewerId' => (int) Auth::id(),
            'viewerName' => Auth::user()?->name,
            'chat' => $this->chatConfiguration(),
            'bookingGroups' => [
                [
                    'key' => 'hosted',
                    'label' => 'Meetings with me',
                    'items' => $hosted,
                ],
                [
                    'key' => 'booked',
                    'label' => 'Meetings I booked',
                    'items' => $booked,
                ],
            ],
            'upcomingBookings' => collect(array_merge($hosted, $booked))
                ->sortBy(fn (array $booking) => $booking['sessionDateKey'] ?? '')
                ->values()
                ->all(),
            'supportUrl' => route('mentor.support.index'),
        ];
    }

    private function transformBooking(Booking $booking, string $perspective): array
    {
        $sessionAt = $booking->sessionAtInTimezone();
        $bookerName = $booking->booker?->name ?? 'Booker';
        $hostMentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $hostMentorMeta = collect([
            $booking->mentor?->title,
            $this->programLabel($booking->mentor?->program_type),
            $booking->mentor?->grad_school_display,
        ])->filter()->implode(' • ');
        $counterpartName = $perspective === 'booked' ? $hostMentorName : $bookerName;
        $counterpartDisplay = $perspective === 'booked'
            ? trim(implode(' • ', array_filter([$hostMentorName, $hostMentorMeta])))
            : $bookerName;
        $hasHostedMentorNote = $perspective === 'hosted'
            && $booking->mentorNotes->contains(fn ($note) => (int) $note->mentor_id === (int) $booking->mentor_id && ! $note->is_deleted);
        $mentorNotesAllowed = $perspective === 'hosted' && $booking->mentorNotesAllowed();

        return [
            'id' => $booking->id,
            'counterpartName' => $counterpartName,
            'mentorName' => $counterpartName,
            'mentorDisplay' => $counterpartDisplay,
            'serviceName' => $booking->service?->service_name ?? 'Service',
            'serviceSlug' => $booking->service?->service_slug ?? null,
            'meetingType' => $booking->meeting_type,
            'meetingTypeLabel' => $this->meetingTypeLabel($booking->meeting_type),
            'meetingSize' => $this->meetingSizeLabel($booking->session_type),
            'duration' => (int) $booking->duration_minutes,
            'sessionDateKey' => $sessionAt?->toDateString(),
            'sessionDateLabel' => $sessionAt?->format('l, F j, Y'),
            'sessionTimeLabel' => $sessionAt?->format('g:i A'),
            'sessionMonthLabel' => $sessionAt?->format('F Y'),
            'meetingLink' => $this->meetingLinkForPerspective($booking, $perspective),
            'meetingProvider' => $this->meetingPresenter->providerLabel($booking),
            'meetingLinkLabel' => $this->meetingLinkLabelForPerspective($booking, $perspective),
            'meetingLinkStatus' => $this->calendarSyncStatus($booking),
            'meetingLinkStatusMessage' => $this->meetingPresenter->statusMessage($booking),
            'meetingAccessAllowed' => $this->meetingPresenter->accessAllowed($booking),
            'meetingAccessMessage' => $this->meetingPresenter->accessMessage($booking),
            'meetingAccessOpensAt' => $this->meetingPresenter->accessOpensAt($booking),
            'meetingState' => $this->meetingPresenter->scheduledState($booking),
            'meetingStateLabel' => $this->meetingPresenter->scheduledStateLabel($booking),
            'attendanceStatus' => $this->meetingPresenter->attendanceStatus($booking),
            'attendanceLabel' => $this->meetingPresenter->attendanceLabel($booking),
            'feedbackAllowed' => $this->meetingPresenter->feedbackAllowed($booking),
            'feedbackUnlockReason' => $this->meetingPresenter->feedbackUnlockReason($booking),
            'status' => $booking->status,
            'statusLabel' => $this->statusLabel($booking->status),
            'bookingGroup' => $perspective,
            'relationshipLabel' => $perspective === 'booked' ? 'Booked by you' : 'Hosted by you',
            'isUpcoming' => $this->meetingPresenter->scheduledState($booking) !== 'ended',
            'isTodayOrFuture' => $sessionAt ? $sessionAt->greaterThanOrEqualTo(now()->startOfDay()) : false,
            'canCancel' => in_array((string) $booking->status, ['pending', 'confirmed'], true)
                && $booking->isSelfCancellationWindowOpen(),
            'cancelUrl' => route('mentor.bookings.cancel', $booking->id),
            'cancelPolicyCopy' => 'Self-service cancellation is available until 24 hours before the meeting. After that, please contact support.',
            'mentorNotesAvailable' => $mentorNotesAllowed,
            'mentorNotesUrl' => $mentorNotesAllowed ? route('mentor.notes.bookings.edit', $booking->id) : null,
            'mentorNotesSubmitted' => $hasHostedMentorNote,
            'mentorNotesLabel' => $perspective === 'hosted'
                ? ($hasHostedMentorNote ? 'Edit Session Notes' : 'Add Session Notes')
                : 'Mentor Notes Unavailable',
            'mentorNotesHelper' => $perspective === 'hosted'
                ? ($mentorNotesAllowed ? 'Internal notes stay visible to mentors only.' : $booking->mentorNotesMessage())
                : 'Mentor notes can only be added for sessions hosted by you.',
            'chatThreadUrl' => route('mentor.bookings.chat.index', $booking->id),
            'chatSendUrl' => route('mentor.bookings.chat.store', $booking->id),
            'chatChannel' => 'booking.'.$booking->id,
        ];
    }

    private function chatConfiguration(): array
    {
        return [
            'enabled' => true,
            'authEndpoint' => url('/broadcasting/auth'),
            'realtime' => [
                'enabled' => (bool) config('broadcasting.connections.reverb.key'),
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host')
                    ?: config('reverb.servers.reverb.hostname')
                    ?: request()->getHost(),
                'port' => (int) (
                    config('broadcasting.connections.reverb.options.port')
                    ?? config('reverb.servers.reverb.port')
                    ?? 8080
                ),
                'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
            ],
        ];
    }

    private function meetingSizeLabel(?string $sessionType): string
    {
        return match ($sessionType) {
            '1on3' => '1 on 3',
            '1on5' => '1 on 5',
            'office_hours' => 'Office Hours',
            default => '1 on 1',
        };
    }

    private function meetingTypeLabel(?string $meetingType): string
    {
        return match ($meetingType) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            null, '' => 'Meeting',
            default => str_replace('_', ' ', ucfirst((string) $meetingType)),
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'confirmed' => 'Booked',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'cancelled', 'cancelled_pending_refund' => 'Cancelled',
            'no_show' => 'No Show',
            default => str_replace('_', ' ', ucfirst((string) ($status ?: 'Booked'))),
        };
    }

    private function programLabel(?string $programType): ?string
    {
        return match ($programType) {
            'mba' => 'MBA',
            'law' => 'Law',
            'cmhc' => 'Counseling',
            'mft' => 'Marriage & Family Therapy',
            'msw' => 'Social Work',
            'clinical_psy' => 'Clinical Psychology',
            'therapy' => 'Therapy',
            'other', null, '' => null,
            default => str_replace('_', ' ', ucfirst((string) $programType)),
        };
    }

    private function bookingCollections(Mentor $mentor): array
    {
        $relations = ['booker:id,name,email,avatar_url', 'mentor.user:id,name,email,avatar_url', 'service', 'officeHourSession', 'mentorNotes:id,booking_id,mentor_id,is_deleted'];

        return [
            Booking::query()
                ->with($relations)
                ->where('mentor_id', $mentor->id)
                ->orderByDesc('session_at')
                ->get(),
            Booking::query()
                ->with($relations)
                ->where('student_id', Auth::id())
                ->where('mentor_id', '!=', $mentor->id)
                ->orderByDesc('session_at')
                ->get(),
        ];
    }

    private function defaultSelectedBooking(Collection $hostedBookings, Collection $bookedBookings): ?Booking
    {
        $combined = $hostedBookings
            ->concat($bookedBookings)
            ->sortBy('session_at')
            ->values();

        return $combined->first(fn (Booking $booking) => $booking->session_at?->isFuture())
            ?? $combined->sortByDesc('session_at')->first();
    }

    private function bookingPerspective(Booking $booking, Mentor $mentor): string
    {
        return (int) $booking->mentor_id === (int) $mentor->id ? 'hosted' : 'booked';
    }

    private function meetingLinkForPerspective(Booking $booking, string $perspective): ?string
    {
        if ($perspective === 'hosted' && $this->isSyncedZoomBooking($booking)) {
            return route('mentor.bookings.start-meeting', $booking->id);
        }

        return $this->meetingLink($booking);
    }

    private function meetingLinkLabelForPerspective(Booking $booking, string $perspective): string
    {
        if ($perspective === 'hosted' && $this->isSyncedZoomBooking($booking)) {
            return 'Start Zoom Meeting';
        }

        return $this->meetingPresenter->linkLabel($booking);
    }

    private function isSyncedZoomBooking(Booking $booking): bool
    {
        return $this->calendarProvider($booking) === 'zoom'
            && $this->calendarSyncStatus($booking) === 'synced'
            && filled($this->externalMeetingId($booking));
    }

    private function bookingWithSharedOfficeHoursMeeting(Booking $booking): Booking
    {
        if ($booking->session_type !== 'office_hours') {
            return $booking;
        }

        $booking->forceFill([
            'meeting_link' => $this->meetingLink($booking),
            'external_calendar_event_id' => $this->externalMeetingId($booking),
            'calendar_provider' => $this->calendarProvider($booking),
            'calendar_sync_status' => $this->calendarSyncStatus($booking),
            'calendar_last_error' => $booking->officeHourSession?->calendar_last_error ?: $booking->calendar_last_error,
        ]);

        return $booking;
    }

    private function meetingLink(Booking $booking): ?string
    {
        if ($booking->session_type === 'office_hours') {
            return $booking->officeHourSession?->meeting_link ?: $booking->meeting_link;
        }

        return $booking->meeting_link;
    }

    private function externalMeetingId(Booking $booking): ?string
    {
        if ($booking->session_type === 'office_hours') {
            return $booking->officeHourSession?->external_calendar_event_id ?: $booking->external_calendar_event_id;
        }

        return $booking->external_calendar_event_id;
    }

    private function calendarProvider(Booking $booking): ?string
    {
        if ($booking->session_type === 'office_hours') {
            return $booking->officeHourSession?->calendar_provider ?: $booking->calendar_provider;
        }

        return $booking->calendar_provider;
    }

    private function calendarSyncStatus(Booking $booking): string
    {
        if ($booking->session_type === 'office_hours') {
            return (string) ($booking->officeHourSession?->calendar_sync_status ?: $booking->calendar_sync_status ?: 'not_synced');
        }

        return (string) ($booking->calendar_sync_status ?: 'not_synced');
    }
}
