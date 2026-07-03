<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
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
use App\Services\EmailVerificationCodeService;
use Modules\Institutions\app\Models\FeaturedInstitution;
use Modules\Institutions\app\Models\University;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly EmailVerificationCodeService $verificationCodes,
    ) {}

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

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showRegister(): View
    {
        return $this->renderLandingAuth('signup');
    }

    public function searchUniversities(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $universities = University::query()
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($queryBuilder) use ($query): void {
                    $queryBuilder
                        ->where('name', 'like', '%'.$query.'%')
                        ->orWhere('display_name', 'like', '%'.$query.'%');
                });
            })
            ->orderByRaw('COALESCE(display_name, name)')
            ->limit(5)
            ->get(['id', 'name', 'display_name', 'country', 'state_province'])
            ->map(fn (University $university): array => [
                'id' => $university->id,
                'name' => $university->display_name ?: $university->name,
                'country' => $university->country,
                'state_province' => $university->state_province,
            ]);

        return response()->json($universities);
    }

    public function showForgotPassword(): View
    {
        return view('auth::forgot-password', [
            'passwordEmailRoute' => route('password.email'),
        ]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth::reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'passwordUpdateRoute' => route('password.update'),
        ]);
    }

    public function showAdminForgotPassword(): View
    {
        return view('auth::forgot-password', [
            'passwordEmailRoute' => route('admin.password.email'),
        ]);
    }

    public function showAdminResetPassword(Request $request, string $token): View
    {
        return view('auth::reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'passwordUpdateRoute' => route('admin.password.update'),
        ]);
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
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
        ]);
    }

    private function getFeaturedInstitutions()
    {
        return FeaturedInstitution::with('university')
            ->orderBy('sort_order')
            ->get()
            ->pluck('university')
            ->filter(fn ($u) => $u !== null && ! empty($u->logo_url));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Log::info('Registration request received.', [
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'] ?? null,
            'program_level' => $validated['program_level'] ?? null,
            'mentor_type' => $validated['mentor_type'] ?? null,
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

    public function sendAdminResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = __(Password::RESET_LINK_SENT);
        $email = (string) $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if ($user && $user->hasRole('admin')) {
            $token = Password::broker()->createToken($user);

            $user->notify(new \App\Notifications\QueuedResetPassword(
                $token,
                $user,
                route('admin.password.reset', ['token' => $token, 'email' => $user->email]),
            ));
        }

        return back()->with('status', $status);
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

    public function resetAdminPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', (string) $request->input('email'))->first();

        if (! $user || ! $user->hasRole('admin')) {
            return back()->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

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

        return redirect()->route('admin.login')
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

    public function verifyEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user, false)
                ->with('status', 'Your email address is already verified.');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'code_digits' => ['nullable', 'array', 'size:6'],
            'code_digits.*' => ['nullable', 'string', 'regex:/^\d?$/'],
        ]);

        $code = $validated['code'] ?? implode('', $validated['code_digits'] ?? []);
        $result = $this->verificationCodes->verify($user, $code);

        if (!$result->valid) {
            return back()
                ->withErrors(['code' => $result->message])
                ->withInput();
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectAfterAuth($user, false)
            ->with('status', $result->message);
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($user, false)
                ->with('status', 'Your email address is already verified.');
        }

        if (!$this->verificationCodes->send($user)) {
            return back()->withErrors([
                'resend' => 'Please wait a moment before requesting another verification code.',
            ]);
        }

        return back()->with('status', 'A fresh 6-digit verification code has been sent to your email address.');
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
