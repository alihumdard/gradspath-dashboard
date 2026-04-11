<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use Modules\Auth\app\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\app\Http\Requests\ResetPasswordRequest;

class AuthController extends Controller
{
    // ── Guest: Show Pages ────────────────────────────────────────────────────

    public function showLogin(): View
    {
        return $this->renderLandingAuth('login');
    }

    public function showRegister(): View
    {
        return $this->renderLandingAuth('signup');
    }

    public function showForgotPassword(): View
    {
        return view('auth::forgot-password');
    }

    public function showResetPassword(string $token): View
    {
        return view('auth::reset-password', ['token' => $token]);
    }

    // ── Login / Logout ───────────────────────────────────────────────────────

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        // Redirect based on role
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        return match (true) {
            $user->hasRole('admin')  => redirect()->route('admin.dashboard'),
            $user->hasRole('mentor') => redirect()->route('mentor.dashboard'),
            default                  => redirect()->route('student.dashboard'),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function renderLandingAuth(string $modal): View
    {
        return view('landing_page.index', [
            'authModal' => $modal,
        ]);
    }

    // ── Register ─────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign Spatie role from form selection (student or mentor)
            $role = in_array($request->role, ['student', 'mentor']) ? $request->role : 'student';
            $user->assignRole($role);

            // Create credit wallet for all users
            DB::table('user_credits')->insert([
                'user_id'    => $user->id,
                'balance'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create user settings with defaults
            DB::table('user_settings')->insert([
                'user_id'             => $user->id,
                'theme'               => 'light',
                'email_notifications' => true,
                'sms_notifications'   => false,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // If registering as mentor, create pending mentor profile
            if ($role === 'mentor') {
                $mentorType = match ($request->program_level) {
                    'undergrad', 'grad' => 'graduate',
                    default             => 'professional',
                };

                DB::table('mentors')->insert([
                    'user_id'              => $user->id,
                    'mentor_type'          => $mentorType,
                    'grad_school_display'  => $request->institution,
                    'status'               => 'pending',   // admin must approve
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            Auth::login($user);
        });

        // Route based on role
        $user = Auth::user();

        if ($user->hasRole('mentor')) {
            return redirect()->route('login')
                ->with('success', 'Mentor application submitted! You will be notified once approved.');
        }

        return redirect()->route('discovery.dashboard')
            ->with('success', 'Welcome to Grads Path!');
    }

    // ── Password Reset ───────────────────────────────────────────────────────

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        // Generate a token and store it in our custom password_resets table
        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($token),
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
            ]
        );

        // TODO: Dispatch SendPasswordResetEmailJob with $token
        // Mail::to($request->email)->queue(new PasswordResetMail($token));

        return back()->with('status', 'If that email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['token' => 'Invalid or expired reset link.']);
        }

        if (!Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'Invalid reset token.']);
        }

        if (now()->isAfter($record->expires_at)) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'This reset link has expired. Please request a new one.']);
        }

        // Update password
        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        // Delete used token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', 'Password reset successfully. Please sign in.');
    }
}
