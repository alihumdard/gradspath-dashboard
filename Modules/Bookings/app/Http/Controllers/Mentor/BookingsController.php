<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class BookingsController extends Controller
{
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
            ->with(['booker:id,name,email,avatar_url', 'mentor.user:id,name,avatar_url', 'service'])
            ->findOrFail($id);
        Gate::authorize('view', $booking);

        [$hostedBookings, $bookedBookings] = $this->bookingCollections($mentor);

        return view('bookings::mentor.index', [
            'bookings' => $hostedBookings,
            'selectedBooking' => $booking,
            'bookingPageData' => $this->buildBookingDetailsPayload($mentor, $hostedBookings, $bookedBookings, $booking),
        ]);
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

        return [
            'id' => $booking->id,
            'counterpartName' => $counterpartName,
            'mentorName' => $counterpartName,
            'mentorDisplay' => $counterpartDisplay,
            'serviceName' => $booking->service?->service_name ?? 'Service',
            'serviceSlug' => $booking->service?->service_slug ?? null,
            'meetingType' => $booking->meeting_type,
            'meetingSize' => $this->meetingSizeLabel($booking->session_type),
            'duration' => (int) $booking->duration_minutes,
            'sessionDateKey' => $sessionAt?->toDateString(),
            'sessionDateLabel' => $sessionAt?->format('l, F j, Y'),
            'sessionTimeLabel' => $sessionAt?->format('g:i A'),
            'sessionMonthLabel' => $sessionAt?->format('F Y'),
            'meetingLink' => $booking->meeting_link,
            'meetingProvider' => $this->meetingProviderLabel($booking),
            'meetingLinkLabel' => $this->meetingLinkLabel($booking),
            'meetingLinkStatus' => (string) ($booking->calendar_sync_status ?: 'not_synced'),
            'meetingLinkStatusMessage' => $this->meetingLinkStatusMessage($booking),
            'status' => $booking->status,
            'bookingGroup' => $perspective,
            'relationshipLabel' => $perspective === 'booked' ? 'Booked by you' : 'Hosted by you',
            'isUpcoming' => $sessionAt ? $sessionAt->isFuture() : false,
            'isTodayOrFuture' => $sessionAt ? $sessionAt->greaterThanOrEqualTo(now()->startOfDay()) : false,
            'canCancel' => in_array((string) $booking->status, ['pending', 'confirmed'], true)
                && $booking->isSelfCancellationWindowOpen(),
            'cancelUrl' => route('mentor.bookings.cancel', $booking->id),
            'cancelPolicyCopy' => 'Self-service cancellation is available until 24 hours before the meeting. After that, please contact support.',
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
        $relations = ['booker:id,name,email,avatar_url', 'mentor.user:id,name,avatar_url', 'service'];

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

    private function meetingLinkStatusMessage(Booking $booking): string
    {
        if ($booking->meeting_link) {
            return $this->isGoogleMeetLink($booking)
                ? 'Google Meet link is ready.'
                : ($booking->calendar_provider === 'google_calendar'
                    ? 'Google Calendar event link is ready.'
                    : 'Meeting link is ready.');
        }

        return match ((string) $booking->calendar_sync_status) {
            'synced' => $booking->calendar_provider === 'google_calendar'
                ? 'Google Calendar event is ready.'
                : 'Meeting link is ready.',
            'failed' => 'Meeting link could not be generated automatically yet.',
            'skipped' => 'Meeting link has not been generated for this booking yet.',
            default => 'Meeting link will be shared soon.',
        };
    }

    private function meetingProviderLabel(Booking $booking): string
    {
        if ($this->isGoogleMeetLink($booking)) {
            return 'Google Meet';
        }

        if ($booking->calendar_provider === 'google_calendar') {
            return 'Google Calendar';
        }

        return 'Meeting Link';
    }

    private function meetingLinkLabel(Booking $booking): string
    {
        if ($this->isGoogleMeetLink($booking)) {
            return 'Join Google Meet';
        }

        if ($booking->calendar_provider === 'google_calendar') {
            return 'Open Calendar Event';
        }

        return 'Open Meeting Link';
    }

    private function isGoogleMeetLink(Booking $booking): bool
    {
        return $booking->meeting_type === 'google_meet'
            || str_contains((string) $booking->meeting_link, 'meet.google.com');
    }
}
