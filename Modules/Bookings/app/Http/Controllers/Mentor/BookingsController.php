<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class BookingsController extends Controller
{
    public function index(Request $request): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();

        $bookings = Booking::query()
            ->with(['student:id,name,email,avatar_url', 'service'])
            ->where('mentor_id', $mentor->id)
            ->orderByDesc('session_at')
            ->paginate((int) $request->integer('per_page', 20));

        $selectedBooking = $bookings->getCollection()
            ->sortBy('session_at')
            ->first(fn (Booking $booking) => $booking->session_at?->isFuture())
            ?? $bookings->first();

        return view('bookings::mentor.index', [
            'bookings' => $bookings,
            'selectedBooking' => $selectedBooking,
            'bookingPageData' => $this->buildBookingDetailsPayload($bookings, $selectedBooking),
        ]);
    }

    public function show(int $id): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();

        $booking = Booking::query()
            ->with(['student:id,name,email,avatar_url', 'service'])
            ->where('mentor_id', $mentor->id)
            ->findOrFail($id);

        $bookings = Booking::query()
            ->with(['student:id,name,email,avatar_url', 'service'])
            ->where('mentor_id', $mentor->id)
            ->orderByDesc('session_at')
            ->paginate(20);

        return view('bookings::mentor.index', [
            'bookings' => $bookings,
            'selectedBooking' => $booking,
            'bookingPageData' => $this->buildBookingDetailsPayload($bookings, $booking),
        ]);
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
            'selectedBooking' => $selectedBooking ? $this->transformBooking($selectedBooking) : null,
            'serviceCatalog' => $serviceCatalog,
            'counterpartLabel' => 'Student',
            'viewerRoleLabel' => 'You',
            'viewerId' => (int) Auth::id(),
            'chat' => $this->chatConfiguration(),
            'upcomingBookings' => $bookings->getCollection()
                ->sortBy('session_at')
                ->map(fn (Booking $booking) => $this->transformBooking($booking))
                ->values()
                ->all(),
            'supportUrl' => route('mentor.support.index'),
        ];
    }

    private function transformBooking(Booking $booking): array
    {
        $sessionAt = $booking->session_at;
        $studentName = $booking->student?->name ?? 'Student';

        return [
            'id' => $booking->id,
            'counterpartName' => $studentName,
            'mentorName' => $studentName,
            'mentorDisplay' => $studentName,
            'serviceName' => $booking->service?->service_name ?? 'Service',
            'serviceSlug' => $booking->service?->service_slug ?? null,
            'meetingType' => $booking->meeting_type,
            'meetingSize' => $this->meetingSizeLabel($booking->session_type),
            'duration' => (int) $booking->duration_minutes,
            'sessionDateKey' => $sessionAt?->toDateString(),
            'sessionDateLabel' => $sessionAt?->format('l, F j, Y'),
            'sessionTimeLabel' => $sessionAt?->format('g:i A'),
            'sessionMonthLabel' => $sessionAt?->format('F Y'),
            'zoomLink' => $booking->meeting_link,
            'isUpcoming' => $sessionAt ? $sessionAt->isFuture() : false,
            'isTodayOrFuture' => $sessionAt ? $sessionAt->greaterThanOrEqualTo(now()->startOfDay()) : false,
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
                'host' => env('REVERB_HOST', request()->getHost()),
                'port' => (int) env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
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
}
