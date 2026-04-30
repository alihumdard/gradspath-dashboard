<?php

namespace Modules\Bookings\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Modules\Bookings\app\Exceptions\BookingException;
use Modules\Bookings\app\Http\Requests\CancelBookingRequest;
use Modules\Bookings\app\Http\Requests\CreateBookingRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingPresenter;
use Modules\Bookings\app\Services\BookingPageService;
use Modules\Bookings\app\Services\BookingService;
use Modules\OfficeHours\app\Services\OfficeHourServiceChoiceService;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly BookingPageService $bookingPage,
        private readonly BookingMeetingPresenter $meetingPresenter,
        private readonly OfficeHourServiceChoiceService $officeHourChoices,
    ) {}

    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['mentor.user:id,name,email,avatar_url', 'mentor.universityProgram:id,program_name', 'mentor.services', 'service', 'officeHourSession.currentService', 'officeHourSession.schedule.mentor.services'])
            ->where('student_id', Auth::id())
            ->orderByDesc('session_at')
            ->paginate((int) $request->integer('per_page', 20));

        $selectedBooking = $bookings->getCollection()
            ->sortBy('session_at')
            ->first(fn (Booking $booking) => $booking->session_at?->isFuture())
            ?? $bookings->first();

        return view('bookings::student.index', [
            'bookings' => $bookings,
            'selectedBooking' => $selectedBooking,
            'bookingPageData' => $this->buildBookingDetailsPayload($bookings, $selectedBooking),
        ]);
    }

    public function create(Request $request, ?int $id = null): View
    {
        $mentorId = (int) ($id ?? $request->integer('mentor_id'));
        $selectedMentor = $this->bookingPage->getSelectedMentor($mentorId);

        return view('bookings::student.create', [
            'mentorId' => $mentorId,
            'selectedMentor' => $selectedMentor,
            'bookingPageData' => $this->bookingPage->buildBookingPageData($selectedMentor, Auth::user(), [
                'portal' => 'student',
                'allow_office_hours' => true,
                'max_meeting_size' => 5,
                'selected_service' => (string) $request->query('service', ''),
            ]),
            'mentors' => Mentor::query()
                ->with('user:id,name')
                ->where('status', 'active')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'services' => ServiceConfig::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function store(CreateBookingRequest $request): RedirectResponse
    {
        $service = ServiceConfig::query()->findOrFail((int) $request->validated()['service_config_id']);
        $sessionType = (string) $request->validated()['session_type'];

        if (! $service->is_office_hours && $this->amountCharged($service, $sessionType) > 0) {
            return back()
                ->withErrors(['booking' => 'Please complete payment with Stripe before creating this booking.'])
                ->withInput();
        }

        try {
            $booking = $this->bookings->createBooking(
                Auth::user(),
                $request->validated(),
                ['charge_credits' => (bool) $service->is_office_hours]
            );
        } catch (BookingException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('student.bookings.show', $booking->id)
            ->with('success', 'Booking created successfully.')
            ->with('office_hours_service_choice_booking_id', $booking->session_type === 'office_hours' ? $booking->id : null);
    }

    public function show(int $id): View
    {
        $booking = Booking::query()
            ->with(['mentor.user', 'mentor.universityProgram:id,program_name', 'mentor.services', 'service', 'student', 'officeHourSession.currentService', 'officeHourSession.schedule.mentor.services'])
            ->findOrFail($id);
        Gate::authorize('view', $booking);

        $bookings = Booking::query()
            ->with(['mentor.user:id,name,email,avatar_url', 'mentor.universityProgram:id,program_name', 'mentor.services', 'service', 'officeHourSession.currentService', 'officeHourSession.schedule.mentor.services'])
            ->where('student_id', Auth::id())
            ->orderByDesc('session_at')
            ->paginate(20);

        return view('bookings::student.index', [
            'bookings' => $bookings,
            'selectedBooking' => $booking,
            'bookingPageData' => $this->buildBookingDetailsPayload($bookings, $booking),
        ]);
    }

    public function joinMeeting(int $id): RedirectResponse
    {
        $booking = Booking::query()->with('officeHourSession')->findOrFail($id);
        Gate::authorize('view', $booking);

        if (! $booking->meetingAccessAllowed()) {
            return redirect()
                ->route('student.bookings.show', $booking->id)
                ->with('error', $booking->meetingAccessMessage());
        }

        $meetingLink = $this->meetingLink($booking);

        if (! filled($meetingLink)) {
            return redirect()
                ->route('student.bookings.show', $booking->id)
                ->with('error', 'Meeting link is not ready yet.');
        }

        return redirect()->away($meetingLink);
    }

    public function cancel(CancelBookingRequest $request, int $id): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($id);
        Gate::authorize('cancel', $booking);

        try {
            $this->bookings->cancelBooking($booking, Auth::user(), $request->validated()['reason'] ?? null);
        } catch (BookingException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()]);
        }

        return redirect()
            ->route('student.bookings.index')
            ->with('success', 'Booking cancelled.');
    }

    private function amountCharged(ServiceConfig $service, string $sessionType): float
    {
        return match ($sessionType) {
            '1on3' => (float) ($service->price_1on3_total ?? ((float) ($service->price_1on3_per_person ?? 0) * 3)),
            '1on5' => (float) ($service->price_1on5_total ?? ((float) ($service->price_1on5_per_person ?? 0) * 5)),
            default => (float) ($service->price_1on1 ?? 0),
        };
    }

    private function buildBookingDetailsPayload($bookings, ?Booking $selectedBooking): array
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

        return [
            'selectedBookingId' => $selectedBooking?->id,
            'autoOpenServiceChoiceBookingId' => session('office_hours_service_choice_booking_id'),
            'selectedBooking' => $selectedBooking ? $this->transformBooking($selectedBooking) : null,
            'serviceCatalog' => $serviceCatalog,
            'counterpartLabel' => 'Mentor',
            'viewerRoleLabel' => 'You',
            'viewerId' => (int) Auth::id(),
            'viewerName' => Auth::user()?->name,
            'chat' => $this->chatConfiguration(),
            'upcomingBookings' => $bookings->getCollection()
                ->sortBy('session_at')
                ->map(fn (Booking $booking) => $this->transformBooking($booking))
                ->values()
                ->all(),
            'supportUrl' => route('student.support.index'),
        ];
    }

    private function transformBooking(Booking $booking): array
    {
        $sessionAt = $booking->sessionAtInTimezone();
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $officeHourSession = $booking->officeHourSession;
        $officeHourFocusName = $officeHourSession?->currentService?->service_name;
        $serviceChoice = $officeHourSession
            ? $this->officeHourChoices->payload($officeHourSession, Auth::user())
            : null;
        $mentorMeta = collect([
            $booking->mentor?->title,
            $this->programLabel($booking->mentor?->program_type),
            $booking->mentor?->grad_school_display,
        ])->filter()->implode(' • ');

        return [
            'id' => $booking->id,
            'counterpartName' => $mentorName,
            'mentorName' => $mentorName,
            'mentorDisplay' => trim(implode(' • ', array_filter([$mentorName, $mentorMeta]))),
            'mentorEmail' => $booking->mentor?->user?->email,
            'mentorProgram' => $booking->mentor?->universityProgram?->program_name
                ?: $this->programLabel($booking->mentor?->program_type),
            'mentorType' => $this->mentorTypeLabel($booking->mentor?->mentor_type),
            'mentorSchool' => $booking->mentor?->grad_school_display,
            'mentorTitle' => $booking->mentor?->title,
            'serviceName' => $booking->service?->service_name ?? 'Service',
            'serviceSlug' => $booking->service?->service_slug ?? null,
            'officeHoursFocusName' => $officeHourFocusName,
            'serviceChoice' => $serviceChoice,
            'meetingType' => $booking->meeting_type,
            'meetingSize' => $this->meetingSizeLabel($booking->session_type),
            'duration' => (int) $booking->duration_minutes,
            'sessionDateKey' => $sessionAt?->toDateString(),
            'sessionDateLabel' => $sessionAt?->format('l, F j, Y'),
            'sessionTimeLabel' => $sessionAt?->format('g:i A'),
            'sessionMonthLabel' => $sessionAt?->format('F Y'),
            'meetingLink' => $this->meetingLinkForBooking($booking),
            'meetingProvider' => $this->meetingPresenter->providerLabel($booking),
            'meetingLinkLabel' => $this->meetingPresenter->linkLabel($booking),
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
            'feedbackSubmitted' => (bool) $booking->student_feedback_done,
            'feedbackSubmitUrl' => route('student.feedback.store'),
            'status' => $booking->status,
            'isUpcoming' => $this->meetingPresenter->scheduledState($booking) !== 'ended',
            'isTodayOrFuture' => $sessionAt ? $sessionAt->greaterThanOrEqualTo(now()->startOfDay()) : false,
            'canCancel' => in_array((string) $booking->status, ['pending', 'confirmed'], true)
                && $booking->isSelfCancellationWindowOpen(),
            'cancelUrl' => Route::has('student.bookings.cancel')
                ? route('student.bookings.cancel', $booking->id)
                : null,
            'cancelPolicyCopy' => 'Self-service cancellation is available until 24 hours before the meeting. After that, please contact support.',
            'chatThreadUrl' => route('student.bookings.chat.index', $booking->id),
            'chatSendUrl' => route('student.bookings.chat.store', $booking->id),
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

    private function mentorTypeLabel(?string $mentorType): ?string
    {
        return match ($mentorType) {
            'graduate' => 'Graduate Mentor',
            'professional' => 'Professional Mentor',
            default => null,
        };
    }

    private function meetingLinkForBooking(Booking $booking): ?string
    {
        if ($this->isSyncedZoomBooking($booking)) {
            return route('student.bookings.join-meeting', $booking->id);
        }

        return $this->meetingLink($booking);
    }

    private function isSyncedZoomBooking(Booking $booking): bool
    {
        return $this->calendarProvider($booking) === 'zoom'
            && $this->calendarSyncStatus($booking) === 'synced'
            && filled($this->externalMeetingId($booking));
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
