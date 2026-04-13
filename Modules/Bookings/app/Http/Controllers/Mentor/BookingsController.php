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

        return view('bookings::mentor.index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(int $id): View
    {
        $mentor = Mentor::query()->where('user_id', Auth::id())->firstOrFail();

        $booking = Booking::query()
            ->with(['student:id,name,email,avatar_url', 'service'])
            ->where('mentor_id', $mentor->id)
            ->findOrFail($id);

        return view('bookings::mentor.index', [
            'bookings' => Booking::query()
                ->with(['student:id,name,email,avatar_url', 'service'])
                ->where('mentor_id', $mentor->id)
                ->orderByDesc('session_at')
                ->paginate(20),
            'selectedBooking' => $booking,
        ]);
    }
}
