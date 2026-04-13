<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

    $this->get('/admin')
        ->assertOk()
        ->assertViewIs('auth::admin.login');

    $this->get('/register')
        ->assertOk()
        ->assertViewHas('authModal', 'signup');

    $this->get('/forgot-password')->assertOk();

    $this->get('/reset-password/test-token')
        ->assertOk()
        ->assertViewHas('token', 'test-token');
});

it('renders the admin login page at /admin for guests', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertViewIs('auth::admin.login');
});

it('redirects guests to the admin login page for protected admin routes', function () {
    $this->get('/admin/dashboard')
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

    $this->post('/admin/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('redirects authenticated admins from /admin to the admin dashboard', function () {
    $created = User::factory()->create([
        'email' => 'existing-admin-' . Str::uuid() . '@example.com',
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user = User::query()->findOrFail($created->id);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('admin.dashboard'));
});

it('registers a student and creates defaults', function () {
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
        ->assertRedirect(route('student.dashboard'));

    $user = User::query()->where('email', $payload['email'])->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('student'))->toBeTrue();

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

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
});

it('registers a student from the landing page and reaches the dashboard after redirect', function () {
    $email = 'student-landing-' . Str::uuid() . '@example.com';

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
        ->assertRedirect(route('student.dashboard'));

    $this->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('STUDENT PORTAL', false);

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

it('accepts registration with .edu country domains like .edu.pk', function () {
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
        ->assertRedirect(route('student.dashboard'));

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

it('accepts registration when email is not .edu', function () {
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

    $this->post('/register', $payload)
        ->assertRedirect(route('student.dashboard'));

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

it('registers even when selected role was missing before signup', function () {
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
        ->assertRedirect(route('student.dashboard'));

    expect(Role::query()->where('name', 'student')->where('guard_name', 'web')->exists())->toBeTrue();

    $user = User::query()->where('email', $payload['email'])->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('student'))->toBeTrue();
});

it('registers a mentor and redirects to mentor dashboard', function () {
    $email = 'mentor-auth-' . Str::uuid() . '@gradschool.edu';

    $payload = [
        'name' => 'Mentor User',
        'email' => $email,
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'mentor',
        'program_level' => 'grad',
        'institution' => 'Top Grad School',
    ];

    $this->post('/register', $payload)
        ->assertRedirect(route('mentor.dashboard'))
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

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
});

it('logs in a student and redirects to student dashboard', function () {
    $email = 'login-student-' . Str::uuid() . '@example.com';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user->assignRole('student');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('logs in a mentor and redirects to mentor dashboard', function () {
    $email = 'login-mentor-' . Str::uuid() . '@example.com';

    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('Password123'),
        'is_active' => true,
    ]);
    $user->assignRole('mentor');

    $this->post('/login', [
        'email' => $email,
        'password' => 'Password123',
    ])->assertRedirect(route('mentor.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('logs in an admin and redirects to admin dashboard', function () {
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
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('logs in an admin from the shared login flow', function () {
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
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
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
