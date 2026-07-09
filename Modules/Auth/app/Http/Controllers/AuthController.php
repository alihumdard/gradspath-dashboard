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
use App\Services\PasswordResetCodeService;
use Modules\Institutions\app\Models\FeaturedInstitution;
use Modules\Institutions\app\Models\University;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly EmailVerificationCodeService $verificationCodes,
        private readonly PasswordResetCodeService $passwordResetCodes,
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

    public function showResetPassword(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email');

        if (!$this->passwordResetCodes->isVerified($email)) {
            return redirect()->route('password.request');
        }

        return view('auth::reset-password', [
            'email' => $email,
            'passwordUpdateRoute' => route('password.update'),
        ]);
    }

    public function showAdminForgotPassword(): View
    {
        return view('auth::forgot-password', [
            'passwordEmailRoute' => route('admin.password.email'),
        ]);
    }

    public function showAdminResetPassword(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email');

        if (!$this->passwordResetCodes->isVerified($email)) {
            return redirect()->route('admin.password.request');
        }

        return view('auth::reset-password', [
            'email' => $email,
            'passwordUpdateRoute' => route('admin.password.update'),
        ]);
    }

    public function showVerifyResetCode(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email');

        if ($email === '') {
            return redirect()->route('password.request');
        }

        return view('auth::verify-reset-code', [
            'email' => $email,
            'verifyRoute' => route('password.reset.verify.post'),
            'resendRoute' => route('password.reset.resend'),
        ]);
    }

    public function showAdminVerifyResetCode(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email');

        if ($email === '') {
            return redirect()->route('admin.password.request');
        }

        return view('auth::verify-reset-code', [
            'email' => $email,
            'verifyRoute' => route('admin.password.reset.verify.post'),
            'resendRoute' => route('admin.password.reset.resend'),
        ]);
    }

    public function verifyResetCode(Request $request): RedirectResponse
    {
        return $this->handleVerifyResetCode($request, 'password.reset');
    }

    public function verifyAdminResetCode(Request $request): RedirectResponse
    {
        return $this->handleVerifyResetCode($request, 'admin.password.reset');
    }

    private function handleVerifyResetCode(Request $request, string $resetRouteName): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['nullable', 'string'],
            'code_digits' => ['nullable', 'array', 'size:6'],
            'code_digits.*' => ['nullable', 'string', 'regex:/^\d?$/'],
        ]);

        $email = $validated['email'];
        $code = $validated['code'] ?? implode('', $validated['code_digits'] ?? []);
        $result = $this->passwordResetCodes->verify($email, $code);

        if (!$result->valid) {
            return back()
                ->withErrors(['code' => $result->message])
                ->withInput();
        }

        return redirect()->route($resetRouteName, ['email' => $email]);
    }

    public function resendResetCode(Request $request): RedirectResponse
    {
        return $this->handleResendResetCode($request, 'password.reset.verify');
    }

    public function resendAdminResetCode(Request $request): RedirectResponse
    {
        return $this->handleResendResetCode($request, 'admin.password.reset.verify');
    }

    private function handleResendResetCode(Request $request, string $verifyRouteName): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user && $this->passwordResetCodes->send($user, true)) {
            return redirect()->route($verifyRouteName, ['email' => $validated['email']])
                ->with('status', 'A fresh 6-digit verification code has been sent to your email address.');
        }

        return redirect()->route($verifyRouteName, ['email' => $validated['email']])
            ->withErrors(['resend' => 'Please wait a moment before requesting another verification code.']);
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
        $isAdmin = Auth::check() && Auth::user()->hasRole('admin');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isAdmin) {
            return redirect()->route('admin.login');
        }

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

        $mentorCounts = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->select('program_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('program_type')
            ->pluck('count', 'program_type');

        $therapyPathwayCount = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->whereIn('program_type', ['cmhc', 'msw', 'mft', 'clinical_psy'])
            ->count();

        $disciplinesByType = \Modules\Institutions\app\Models\Discipline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('landing_page.index', [
            'authModal' => $modal,
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
            'programsByType' => \Modules\Institutions\app\Models\UniversityProgram::getLandingPagePrograms(),
            'mentorCounts' => $mentorCounts,
            'therapyPathwayCount' => $therapyPathwayCount,
            'disciplinesByType' => $disciplinesByType,
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
        return $this->handleSendResetCode($request, 'password.reset.verify');
    }

    public function sendAdminResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if (!$user || !$user->hasRole('admin')) {
            return redirect()->route('admin.password.reset.verify', ['email' => $email])
                ->with('status', 'If an account exists for that email, a verification code has been sent.');
        }

        return $this->handleSendResetCode($request, 'admin.password.reset.verify');
    }

    private function handleSendResetCode(Request $request, string $verifyRouteName): RedirectResponse
    {
        $email = (string) $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $this->passwordResetCodes->send($user, true);
        }

        return redirect()->route($verifyRouteName, ['email' => $email])
            ->with('status', 'If an account exists for that email, a verification code has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        return $this->handleResetPassword($request, 'login');
    }

    public function resetAdminPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', (string) $request->input('email'))->first();

        if (!$user || !$user->hasRole('admin')) {
            return back()->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

        return $this->handleResetPassword($request, 'admin.login');
    }

    private function handleResetPassword(ResetPasswordRequest $request, string $loginRouteName): RedirectResponse
    {
        $email = (string) $request->input('email');

        if (!$this->passwordResetCodes->isVerified($email)) {
            return back()->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => __(Password::INVALID_USER)]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        $this->passwordResetCodes->forget($email);

        return redirect()->route($loginRouteName)
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
