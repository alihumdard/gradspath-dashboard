<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
});

function createAdminNavigationUser(): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('admin');

    return $user;
}

it('redirects the legacy admin dashboard route to the overview page', function () {
    $admin = createAdminNavigationUser();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.overview'));
});

it('renders each dedicated admin page with sidebar navigation', function () {
    $admin = createAdminNavigationUser();

    $pages = [
        'admin.overview' => 'Admin Overview',
        'admin.users' => 'Admin Users',
        'admin.mentors' => 'Admin Mentors',
        'admin.services' => 'Admin Services',
        'admin.revenue' => 'Admin Revenue',
        'admin.rankings' => 'Admin Rankings',
        'admin.manual-actions' => 'Manual Controls',
    ];

    foreach ($pages as $routeName => $heading) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Users')
            ->assertSee('Mentors')
            ->assertSee('Services')
            ->assertSee('Revenue')
            ->assertSee('Rankings')
            ->assertSee('Manual Actions')
            ->assertSee($heading);
    }
});
