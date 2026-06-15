<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Bookings\app\Models\Booking;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeedbackCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->hasRole('student')) {
            return $next($request);
        }

        if ($request->routeIs('auth.logout', 'feedback.*', 'student.feedback.*', 'student.bookings.index')) {
            return $next($request);
        }

        $hasOverdueFeedback = Booking::query()
            ->where('student_id', $user->id)
            ->whereNotNull('feedback_due_at')
            ->where('feedback_due_at', '<', now())
            ->where('student_feedback_done', false)
            ->whereIn('status', ['completed'])
            ->exists();

        if ($hasOverdueFeedback) {
            return redirect()
                ->route('student.bookings.index')
                ->with('warning', 'Please complete your pending session feedback before continuing in the student portal.');
        }

        return $next($request);
    }
}
