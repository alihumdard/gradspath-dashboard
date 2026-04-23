<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('student', 'web');
});

function createHorizonUser(string $role = 'student', bool $isActive = true): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => $isActive,
    ]);

    $user->assignRole($role);

    return $user;
}

it('redirects guests away from the horizon dashboard', function () {
    $this->get('/horizon')->assertRedirect(route('login'));
});

it('blocks non-admin users from the horizon dashboard', function () {
    $user = createHorizonUser('student');

    $this->actingAs($user)
        ->get('/horizon')
        ->assertForbidden();
});

it('redirects inactive admins away from the horizon dashboard', function () {
    $user = createHorizonUser('admin', false);

    $this->actingAs($user)
        ->get('/horizon')
        ->assertRedirect(route('login'));
});

it('allows active admins to open the horizon dashboard', function () {
    $admin = createHorizonUser('admin');

    $this->actingAs($admin)
        ->get('/horizon')
        ->assertForbidden();
});

it('uses redis for queues and horizon supervisors', function () {
    expect(config('queue.default'))->toBe('sync');
    expect(config('horizon.defaults.supervisor-1.connection'))->toBe('redis');
    expect(config('horizon.middleware'))->toBe(['web', 'auth', 'active', 'role:admin']);
});
