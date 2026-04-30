<?php

namespace Modules\Bookings\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;
use Symfony\Component\HttpFoundation\Response;

class EnsureBookingParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return $next($request);
        }

        $user = User::query()->find($authUser->getAuthIdentifier());
        if (!$user || $user->hasRole('admin')) {
            return $next($request);
        }

        $bookingId = (int) ($request->route('id') ?? $request->route('booking'));
        if ($bookingId <= 0) {
            abort(404);
        }

        $booking = Booking::query()->findOrFail($bookingId);

        if ((int) $booking->student_id === (int) $user->id) {
            return $next($request);
        }

        if ($booking->hasParticipantUser($user)) {
            return $next($request);
        }

        if ($user->hasRole('mentor')) {
            $mentor = Mentor::query()->where('user_id', $user->id)->first();

            if ($mentor && (int) $mentor->id === (int) $booking->mentor_id) {
                return $next($request);
            }
        }

        abort(403);
    }
}
