<?php

namespace Modules\Auth\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && !$user->is_active) {
            $adminPath = trim((string) config('auth.admin_path'), '/');
            $loginRoute = $user->hasRole('admin') || $request->is($adminPath) || $request->is($adminPath.'/*') || $request->is('horizon') || $request->is('horizon/*')
                ? 'admin.login'
                : 'login';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route($loginRoute)
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        return $next($request);
    }
}
