<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMentorApproved
{
    /**
     * Only allow mentors whose mentor profile status is 'active' to access mentor pages.
     * Mentors whose applications are 'pending' or 'rejected' are redirected away.
     * Non-mentor users pass through untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole('mentor')) {
            // Load mentor relationship — mentors table has status field
            $mentor = $user->mentor;

            if (!$mentor || $mentor->status !== 'active') {
                return redirect()
                    ->route('login')
                    ->withErrors([
                        'role' => match($mentor?->status) {
                            'pending'  => 'Your mentor application is under review. You will be notified once approved.',
                            'rejected' => 'Your mentor application was not approved. Please contact support.',
                            'paused'   => 'Your mentor account has been paused. Please contact support.',
                            default    => 'Your mentor profile is not active.',
                        },
                    ]);
            }
        }

        return $next($request);
    }
}
