<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeedbackCompleted
{
    /**
     * Block students from booking new sessions if they have overdue feedback.
     * The platform requires both parties to submit feedback within 24h of session end.
     * Only applies to users with the 'student' role on the bookings.create route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only applies to students — mentors and admins pass through
        if ($user && $user->hasRole('student')) {
            // Check for any completed session older than 24h with no student feedback
            $hasOverdueFeedback = \DB::table('bookings')
                ->where('student_id', $user->id)
                ->where('status', 'completed')
                ->where('student_feedback_done', false)
                ->where('feedback_due_at', '<', now())
                ->exists();

            if ($hasOverdueFeedback) {
                return redirect()
                    ->route('student.feedback.index')
                    ->with('warning', 'Please complete your pending session feedback before booking a new session.');
            }
        }

        return $next($request);
    }
}
