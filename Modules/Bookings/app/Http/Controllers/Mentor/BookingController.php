<?php

namespace Modules\Bookings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Bookings\app\Exceptions\BookingException;
use Modules\Bookings\app\Http\Requests\CancelBookingRequest;
use Modules\Bookings\app\Http\Requests\CreateBookingRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingPageService;
use Modules\Bookings\app\Services\BookingService;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly BookingPageService $bookingPage,
    ) {}

    public function create(Request $request, ?int $id = null): View
    {
        $mentorId = (int) ($id ?? $request->integer('mentor_id'));
        $viewerMentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();
        $selectedMentor = $this->bookingPage->getSelectedMentor($mentorId);

        abort_if((int) $viewerMentor->id === (int) $selectedMentor->id, 403);

        return view('bookings::student.create', [
            'mentorId' => $mentorId,
            'selectedMentor' => $selectedMentor,
            'portalLayout' => 'layouts.portal-mentor',
            'bookingPageData' => $this->bookingPage->buildBookingPageData($selectedMentor, Auth::user(), [
                'portal' => 'mentor',
                'allow_office_hours' => false,
                'max_meeting_size' => 1,
            ]),
        ]);
    }

    public function store(CreateBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $service = ServiceConfig::query()->findOrFail((int) $data['service_config_id']);
        $sessionType = (string) $data['session_type'];

        if ($sessionType !== '1on1' || (bool) $service->is_office_hours) {
            return back()
                ->withErrors(['booking' => 'Mentor-to-mentor booking currently supports standard 1 on 1 sessions only.'])
                ->withInput();
        }

        if ($this->amountCharged($service, $sessionType) > 0) {
            return back()
                ->withErrors(['booking' => 'Please complete payment with Stripe before creating this booking.'])
                ->withInput();
        }

        try {
            $booking = $this->bookings->createBooking(Auth::user(), $data, ['charge_credits' => false]);
        } catch (BookingException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('mentor.bookings.show', $booking->id)
            ->with('success', 'Booking created successfully.');
    }

    public function cancel(CancelBookingRequest $request, int $id): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($id);
        $this->authorize('cancel', $booking);

        try {
            $this->bookings->cancelBooking($booking, Auth::user(), $request->validated()['reason'] ?? null);
        } catch (BookingException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()]);
        }

        return redirect()
            ->route('mentor.bookings.index')
            ->with('success', 'Booking cancelled.');
    }

    private function amountCharged(ServiceConfig $service, string $sessionType): float
    {
        return match ($sessionType) {
            '1on3' => (float) ($service->price_1on3_per_person ?? 0) * 3,
            '1on5' => (float) ($service->price_1on5_per_person ?? 0) * 5,
            default => (float) ($service->price_1on1 ?? 0),
        };
    }
}
