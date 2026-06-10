<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;
use Symfony\Component\HttpFoundation\Response;

class EnsureMentorSessionNotesCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->hasRole('mentor')) {
            return $next($request);
        }

        if ($request->routeIs('auth.logout', 'mentor.notes.bookings.*')) {
            return $next($request);
        }

        $mentor = Mentor::query()
            ->where('user_id', $user->id)
            ->first(['id']);

        if (! $mentor) {
            return $next($request);
        }

        $overdueBooking = Booking::query()
            ->where('mentor_id', $mentor->id)
            ->whereNotNull('feedback_due_at')
            ->where('feedback_due_at', '<', now())
            ->where('mentor_feedback_done', false)
            ->whereIn('status', ['completed'])
            ->orderBy('feedback_due_at')
            ->first(['id']);

        if (! $overdueBooking) {
            return $next($request);
        }

        return redirect()
            ->route('mentor.notes.bookings.edit', $overdueBooking->id)
            ->with('warning', 'Please complete your required session notes before continuing in the mentor portal.');
    }
}
