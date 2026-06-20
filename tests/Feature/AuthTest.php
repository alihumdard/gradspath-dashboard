<?php

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Models\EmailVerificationCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');
});

it('renders auth pages', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewHas('authModal', 'login');

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertViewIs('auth::admin.login');

    $this->get('/register')
        ->assertOk()
        ->assertViewHas('authModal', 'signup');

    $this->get('/forgot-password')
        ->assertOk()
        ->assertDontSee('Remember your password?')
        ->assertDontSee("Don't have an account?");

    $this->get('/reset-password/test-token')
        ->assertOk()
        ->assertViewHas('token', 'test-token')
        ->assertSee('password-toggle', false)
        ->assertDontSee('Remember your password?');
});

it('renders the email verification notice for unverified users', function () {
    $user = User::factory()->create([
        'email' => 'verify-notice-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Enter verification code');
});

it('renders the admin login page at the secret admin path for guests', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertViewIs('auth::admin.login');
});

it('hides the old admin path when the secret admin path is configured', function () {
    $this->get('/admin')->assertNotFound();
    $this->get('/admin/dashboard')->assertNotFound();
});

it('redirects guests to the admin login page for protected admin routes', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('authenticates an admin from the admin login form', function () {
    $email = 'admin-login-' . Str::uuid() . '@example.com';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user->assignRole('admin');

    $this->post(route('admin.login.post'), [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('admin.dashboard'));

    expect(Auth::id())->toBe($user->id);
});

it('redirects authenticated admins from the secret admin login to the admin dashboard', function () {
    $created = User::factory()->create([
        'email' => 'existing-admin-' . Str::uuid() . '@example.com',
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user = User::query()->findOrFail($created->id);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});

it('registers a student, creates defaults, and sends verification email', function () {
    Notification::fake();

    $email = 'student-auth-' . Str::uuid() . '@college.edu';

    $payload = [
        'name' => 'Student User',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', $payload['email'])->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('student'))->toBeTrue();
    expect($user->hasVerifiedEmail())->toBeFalse();

    $this->assertDatabaseHas('user_credits', [
        'user_id' => $user->id,
        'balance' => 0,
    ]);

    $this->assertDatabaseHas('user_settings', [
        'user_id' => $user->id,
        'theme' => 'light',
        'email_notifications' => 1,
        'sms_notifications' => 0,
    ]);

    $this->assertDatabaseHas('student_profiles', [
        'user_id' => $user->id,
        'institution_text' => 'State University',
        'program_level' => 'undergrad',
    ]);

    Notification::assertSentTo(
        $user,
        QueuedVerifyEmail::class,
        fn (QueuedVerifyEmail $notification): bool => preg_match('/^\d{6}$/', $notification->code) === 1
    );

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
});

it('stores the selected institution id during student registration', function () {
    Notification::fake();

    $university = University::query()->create([
        'name' => 'Boston College',
        'display_name' => 'Boston College',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $email = 'student-with-school-' . Str::uuid() . '@college.edu';

    $payload = [
        'name' => 'Student With Institution',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'Boston College',
        'institution_id' => $university->id,
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', $email)->firstOrFail();

    $this->assertDatabaseHas('student_profiles', [
        'user_id' => $user->id,
        'university_id' => $university->id,
        'institution_text' => 'Boston College',
    ]);
});

it('registers a student from the landing page and sends them to verification notice', function () {
    Notification::fake();

    $email = 'student-landing-' . Str::uuid() . '@example.edu';

    $payload = [
        'name' => 'Landing Student',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $this->from('/')
        ->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Enter verification code');

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

it('accepts registration with .edu country domains like .edu.pk', function () {
    Notification::fake();

    $email = 'suny-' . Str::uuid() . '@student.edu.pk';

    $payload = [
        'name' => 'Student PK Domain',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

it('rejects undergrad registration when email is not .edu', function () {
    Notification::fake();

    $email = 'student-invalid-' . Str::uuid() . '@example.com';

    $payload = [
        'name' => 'Student Invalid Domain',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $response = $this->from('/')
        ->post('/register', $payload);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
});

it('registers even when selected role was missing before signup', function () {
    Notification::fake();

    Role::query()
        ->where('name', 'student')
        ->where('guard_name', 'web')
        ->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $payload = [
        'name' => 'Student Missing Role',
        'email' => 'student-missing-role-' . Str::uuid() . '@campus.edu',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    expect(Role::query()->where('name', 'student')->where('guard_name', 'web')->exists())->toBeTrue();

    $user = User::query()->where('email', $payload['email'])->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('student'))->toBeTrue();
});

it('registers a mentor and redirects to verification notice', function () {
    Notification::fake();

    $email = 'mentor-auth-' . Str::uuid() . '@gradschool.edu';

    $payload = [
        'name' => 'Mentor User',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'mentor',
        'mentor_type' => 'graduate',
        'institution' => 'Top Grad School',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', $payload['email'])->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('mentor'))->toBeTrue();

    $this->assertDatabaseHas('mentors', [
        'user_id' => $user->id,
        'status' => 'active',
        'mentor_type' => 'graduate',
        'grad_school_display' => 'Top Grad School',
    ]);

    $this->assertDatabaseMissing('student_profiles', [
        'user_id' => $user->id,
    ]);

    Notification::assertSentTo($user, QueuedVerifyEmail::class);

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
});

it('registers a professional mentor with the selected mentor type', function () {
    Notification::fake();

    $email = 'professional-mentor-' . Str::uuid() . '@example.com';

    $payload = [
        'name' => 'Professional Mentor',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'mentor',
        'mentor_type' => 'professional',
        'institution' => 'Industry Org',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', $email)->firstOrFail();

    $this->assertDatabaseHas('mentors', [
        'user_id' => $user->id,
        'mentor_type' => 'professional',
        'grad_school_display' => 'Industry Org',
    ]);

    $this->assertDatabaseMissing('student_profiles', [
        'user_id' => $user->id,
    ]);
});

it('rejects graduate mentor registration when email is not .edu', function () {
    Notification::fake();

    $email = 'graduate-mentor-invalid-' . Str::uuid() . '@example.com';

    $payload = [
        'name' => 'Graduate Mentor Invalid',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'mentor',
        'mentor_type' => 'graduate',
        'institution' => 'Top Grad School',
    ];

    $this->from('/')
        ->post('/register', $payload)
        ->assertRedirect('/')
        ->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => $email]);
});

it('normalizes legacy professional mentor signup payloads', function () {
    Notification::fake();

    $email = 'legacy-professional-mentor-' . Str::uuid() . '@example.com';

    $payload = [
        'name' => 'Legacy Professional Mentor',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'mentor',
        'program_level' => 'professional',
        'institution' => 'Industry Org',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', $email)->firstOrFail();

    $this->assertDatabaseHas('mentors', [
        'user_id' => $user->id,
        'mentor_type' => 'professional',
    ]);
});

it('does not accept graduate or professional program levels for student signup', function () {
    Notification::fake();

    $payload = [
        'name' => 'Student Wrong Level',
        'email' => 'student-wrong-level-' . Str::uuid() . '@college.edu',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'grad',
        'institution' => 'State University',
    ];

    $this->from('/')
        ->post('/register', $payload)
        ->assertRedirect('/')
        ->assertSessionHasErrors('program_level');

    $this->assertDatabaseMissing('users', [
        'email' => $payload['email'],
    ]);
});

it('shows duplicate signup email errors inline without global alerts', function () {
    $existing = User::factory()->create([
        'email' => 'duplicate-signup-' . Str::uuid() . '@example.edu',
    ]);

    $payload = [
        'auth_context' => 'signup',
        'name' => 'Duplicate Student',
        'email' => $existing->email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'student',
        'program_level' => 'undergrad',
        'institution' => 'State University',
    ];

    $this->from('/')
        ->post('/register', $payload)
        ->assertRedirect('/')
        ->assertSessionHasErrors('email');

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('An account with this email already exists.')
        ->assertDontSee('We could not create your account:')
        ->assertDontSee('Something went wrong');

    expect(substr_count($response->getContent(), 'An account with this email already exists.'))->toBe(1);
});

it('logs in an unverified student and redirects to verification notice', function () {
    $email = 'login-student-' . Str::uuid() . '@example.edu';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('verification.notice'));

    expect(Auth::id())->toBe($user->id);
});

it('logs in a verified student and redirects to student dashboard', function () {
    $email = 'login-student-verified-' . Str::uuid() . '@example.edu';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $user->assignRole('student');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('student.dashboard'));

    expect(Auth::id())->toBe($user->id);
});

it('logs in an unverified mentor and redirects to verification notice', function () {
    $email = 'login-mentor-' . Str::uuid() . '@example.edu';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('mentor');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('verification.notice'));

    expect(Auth::id())->toBe($user->id);
});

it('logs in a verified mentor and redirects to mentor dashboard', function () {
    $email = 'login-mentor-verified-' . Str::uuid() . '@example.edu';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $user->assignRole('mentor');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('mentor.dashboard'));

    expect(Auth::id())->toBe($user->id);
});

it('rejects admin login from the shared user portal form', function () {
    $email = 'login-admin-' . Str::uuid() . '@example.com';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user->assignRole('admin');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects admin login from the shared login flow', function () {
    $email = 'admin-login-' . Str::uuid() . '@example.com';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user->assignRole('admin');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('resends the verification email for unverified users', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'resend-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('marks the user email as verified with the correct 6 digit code', function () {
    $user = User::factory()->create([
        'email' => 'verify-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    EmailVerificationCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(15),
        'attempts' => 0,
        'last_sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '123456'])
        ->assertRedirect(route('student.dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
});

it('rejects an incorrect 6 digit verification code and records an attempt', function () {
    $user = User::factory()->create([
        'email' => 'wrong-code-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    EmailVerificationCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(15),
        'attempts' => 0,
        'last_sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.verify'), ['code' => '654321'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    expect(EmailVerificationCode::query()->where('user_id', $user->id)->value('attempts'))->toBe(1);
});

it('rejects an expired verification code', function () {
    $user = User::factory()->create([
        'email' => 'expired-code-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    EmailVerificationCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
        'attempts' => 0,
        'last_sent_at' => now()->subMinutes(20),
    ]);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.verify'), ['code' => '123456'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('throttles resend requests for recent verification codes', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'resend-throttle-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    $user->assignRole('student');

    EmailVerificationCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(15),
        'attempts' => 0,
        'last_sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('resend');

    Notification::assertNothingSent();
});

it('redirects verified users away from the verification notice', function () {
    $user = User::factory()->create([
        'email' => 'verified-notice-' . Str::uuid() . '@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $user->assignRole('student');

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('student.dashboard'));
});

it('rejects invalid login credentials', function () {
    $email = 'bad-login-' . Str::uuid() . '@example.com';

    User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);

    $this->post('/login', [
        'email' => $email,
        'password' => 'WrongPassword999',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out authenticated users', function () {
    $created = User::factory()->createOne(['is_active' => true]);
    /** @var User $user */
    $user = User::query()->findOrFail($created->id);
    $user->assignRole('student');

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('blocks inactive authenticated users through active middleware', function () {
    $created = User::factory()->createOne(['is_active' => false]);
    /** @var User $user */
    $user = User::query()->findOrFail($created->id);
    $user->assignRole('student');

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('sends reset link through password broker', function () {
    Password::shouldReceive('sendResetLink')
        ->once()
        ->andReturn(Password::RESET_LINK_SENT);

    $this->post('/forgot-password', ['email' => 'forgot@example.com'])
        ->assertSessionHas('status');
});

it('sends branded reset password emails', function () {
    Notification::fake();

    $user = User::factory()->create([
        'name' => 'Reset Student',
        'email' => 'branded-reset-' . Str::uuid() . '@example.edu',
        'is_active' => true,
    ]);

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->view === 'emails.reset-password'
            && $mail->subject === 'Reset your Grads Paths password'
            && $mail->viewData['userName'] === 'Reset Student'
            && str_contains($mail->viewData['url'], '/reset-password/');
    });
});

it('renders the admin forgot-password page under the secret admin path', function () {
    $this->get(route('admin.password.request'))
        ->assertOk()
        ->assertViewIs('auth::forgot-password');
});

it('sends admin reset emails with the secret admin reset URL', function () {
    Notification::fake();

    $admin = User::factory()->create([
        'name' => 'Reset Admin',
        'email' => 'admin-reset-' . Str::uuid() . '@example.com',
        'is_active' => true,
    ]);
    $admin->assignRole('admin');

    $this->post(route('admin.password.email'), ['email' => $admin->email])
        ->assertSessionHas('status');

    $notifiableAdmin = \App\Models\User::query()->findOrFail($admin->id);

    Notification::assertSentTo($notifiableAdmin, QueuedResetPassword::class, function (QueuedResetPassword $notification) use ($notifiableAdmin) {
        $mail = $notification->toMail($notifiableAdmin);

        return $mail->view === 'emails.reset-password'
            && $mail->subject === 'Reset your Grads Paths password'
            && str_contains($mail->viewData['url'], '/'.config('auth.admin_path').'/reset-password/')
            && str_contains($mail->viewData['url'], 'email='.urlencode($notifiableAdmin->email));
    });
});

it('does not send admin reset emails to non-admin users or unknown emails', function () {
    Notification::fake();

    $student = User::factory()->create([
        'email' => 'student-reset-' . Str::uuid() . '@example.edu',
        'is_active' => true,
    ]);
    $student->assignRole('student');

    $this->post(route('admin.password.email'), ['email' => $student->email])
        ->assertSessionHas('status');

    $this->post(route('admin.password.email'), ['email' => 'missing-admin@example.com'])
        ->assertSessionHas('status');

    $notifiableStudent = \App\Models\User::query()->findOrFail($student->id);

    Notification::assertNotSentTo($notifiableStudent, QueuedResetPassword::class);
});

it('resets an admin password through the admin reset route', function () {
    $admin = User::factory()->create([
        'email' => 'admin-password-reset-' . Str::uuid() . '@example.com',
        'password' => Hash::make('OldPassword123'),
        'is_active' => true,
    ]);
    $admin->assignRole('admin');
    $token = Password::broker()->createToken($admin);

    $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => $admin->email,
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('success');

    $admin->refresh();

    expect(Hash::check('NewPassword123', $admin->password))->toBeTrue();
});

it('returns validation error for invalid forgot password email', function () {
    $this->post('/forgot-password', ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

it('resets password through password broker', function () {
    $user = User::factory()->create([
        'email' => 'reset-user@example.com',
        'password' => Hash::make('OldPassword123'),
        'is_active' => true,
    ]);

    Password::shouldReceive('reset')
        ->once()
        ->andReturnUsing(function (array $credentials, Closure $callback) use ($user) {
            $callback($user, $credentials['password']);

            return Password::PASSWORD_RESET;
        });

    $this->post('/reset-password', [
        'token' => 'valid-token',
        'email' => 'reset-user@example.com',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $user->refresh();

    expect(Hash::check('NewPassword123', $user->password))->toBeTrue();
});

it('shows the reset-password success toast on the login page', function () {
    $this->withSession([
        'success' => 'Password reset successfully. Please sign in.',
    ])->get(route('login'))
        ->assertOk()
        ->assertSee('Password reset successfully. Please sign in.')
        ->assertSee('appToastViewport');
});

it('returns broker error when password reset fails', function () {
    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::INVALID_TOKEN);

    $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'reset-fail@example.com',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])->assertSessionHasErrors('email');
});
