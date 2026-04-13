<?php

namespace Modules\Bookings\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Bookings\app\Http\Requests\CancelBookingRequest;
use Modules\Bookings\app\Http\Requests\CreateBookingRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingService;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['mentor.user:id,name,avatar_url', 'service'])
            ->where('student_id', Auth::id())
            ->orderByDesc('session_at')
            ->paginate((int) $request->integer('per_page', 20));

        return view('bookings::student.index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(Request $request, ?int $id = null): View
    {
        $mentorId = $id ?? $request->integer('mentor_id');

        return view('bookings::student.create', [
            'mentorId' => $mentorId,
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
        try {
            $booking = $this->bookings->createBooking(Auth::user(), $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('student.bookings.show', $booking->id)
            ->with('success', 'Booking created successfully.');
    }

    public function show(int $id): View
    {
        $booking = Booking::query()->with(['mentor.user', 'service', 'student'])->findOrFail($id);
        Gate::authorize('view', $booking);

        return view('bookings::student.index', [
            'bookings' => Booking::query()
                ->with(['mentor.user:id,name,avatar_url', 'service'])
                ->where('student_id', Auth::id())
                ->orderByDesc('session_at')
                ->paginate(20),
            'selectedBooking' => $booking,
        ]);
    }

    public function cancel(CancelBookingRequest $request, int $id): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($id);
        Gate::authorize('cancel', $booking);

        try {
            $this->bookings->cancelBooking($booking, Auth::user(), $request->validated()['reason'] ?? null);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()]);
        }

        return back()->with('success', 'Booking cancelled and marked for refund review.');
    }
}
