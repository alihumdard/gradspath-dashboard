<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\app\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use Modules\Auth\app\Http\Requests\ResetPasswordRequest;
use Modules\Auth\app\Services\AuthService;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showLogin(): View
    {
        return $this->renderLandingAuth('login');
    }

    public function showAdminLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth::admin.login');
    }

    public function adminLogin(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->loginAdminPortal(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        if (!$user) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our admin records.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
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

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->loginUserPortal(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        if (!$user) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our user portal records.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return $this->redirectAfterAuth($user);
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
        $oldAuthContext = session()->getOldInput('auth_context');

        if ($oldAuthContext === 'signup') {
            $modal = 'signup';
        } elseif ($oldAuthContext === 'login') {
            $modal = 'login';
        }

        return view('landing_page.index', [
            'authModal' => $modal,
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Log::info('Registration request received.', [
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'] ?? null,
            'program_level' => $validated['program_level'] ?? null,
            'institution' => $validated['institution'] ?? null,
            'session_id_before_login' => $request->session()->getId(),
        ]);

        try {
            $user = $this->authService->register($validated);

            Log::info('Registration user created.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values()->all(),
            ]);

            Auth::login($user);

            Log::info('Registration user logged in.', [
                'user_id' => $user->id,
                'auth_check' => Auth::check(),
                'auth_user_id' => Auth::id(),
            ]);

            event(new Registered($user));

            $request->session()->regenerate();

            Log::info('Registration session regenerated.', [
                'user_id' => $user->id,
                'session_id_after_login' => $request->session()->getId(),
            ]);

            $redirect = $this->redirectAfterAuth($user);

            Log::info('Registration redirect resolved.', [
                'user_id' => $user->id,
                'target_url' => $redirect->getTargetUrl(),
            ]);

            return $redirect->with('success', 'Welcome to Grads Path!');
        } catch (Throwable $exception) {
            Log::error('Registration failed.', [
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'] ?? null,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __($status));
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')
            ->with('success', 'Password reset successfully. Please sign in.');
    }

    public function showVerifyEmailNotice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user, false);
        }

        return view('auth::verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($request->user(), false)
                ->with('status', 'Your email address is already verified.');
        }

        $request->fulfill();

        return $this->redirectAfterAuth($request->user(), false)
            ->with('status', 'Your email address has been verified.');
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user, false)
                ->with('status', 'Your email address is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A fresh verification link has been sent to your email address.');
    }

    private function redirectAfterAuth(User $user, bool $enforceVerification = true): RedirectResponse
    {
        if (
            $enforceVerification
            && !$user->hasRole('admin')
            && $user instanceof MustVerifyEmail
            && !$user->hasVerifiedEmail()
        ) {
            return redirect()->route('verification.notice');
        }

        return match (true) {
            $user->hasRole('admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('mentor') => redirect()->route('mentor.dashboard'),
            default => redirect()->route('student.dashboard'),
        };
    }
}
