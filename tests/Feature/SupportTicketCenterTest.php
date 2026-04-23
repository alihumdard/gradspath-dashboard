<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Models\SupportTicket;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');

    Queue::fake();
});

function makeSupportUser(string $role, string $prefix = 'support'): User
{
    $user = User::factory()->create([
        'email' => $prefix.'-'.Str::uuid().'@example.com',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $user->assignRole($role);

    return $user;
}

dataset('support portals', [
    'student' => [
        'role' => 'student',
        'indexRoute' => 'student.support.index',
        'storeRoute' => 'student.support.store',
    ],
    'mentor' => [
        'role' => 'mentor',
        'indexRoute' => 'mentor.support.index',
        'storeRoute' => 'mentor.support.store',
    ],
]);

it('renders the support form for authenticated users', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);

    $this->actingAs($user)
        ->get(route($indexRoute))
        ->assertOk()
        ->assertSee('Support')
        ->assertSee('Submit Feedback')
        ->assertSee('Subject')
        ->assertSee('Message')
        ->assertDontSee('My Tickets')
        ->assertDontSee('Ticket Details');
})->with('support portals');

it('creates a support ticket from the form', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);

    $response = $this->from(route($indexRoute))
        ->actingAs($user)
        ->post(route($storeRoute), [
            'subject' => 'Need help with booking reschedule',
            'message' => 'The booked session time is incorrect and I need support.',
        ]);

    $response
        ->assertRedirect(route($indexRoute))
        ->assertSessionHas('success', 'Support ticket created successfully.');

    $ticket = SupportTicket::query()->where('user_id', $user->id)->first();

    expect($ticket)->not->toBeNull();
    expect($ticket->subject)->toBe('Need help with booking reschedule');
    expect($ticket->status)->toBe('open');
})->with('support portals');

it('validates required support ticket fields', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);

    $this->from(route($indexRoute))
        ->actingAs($user)
        ->post(route($storeRoute), [
            'subject' => '',
            'message' => '',
        ])
        ->assertRedirect(route($indexRoute))
        ->assertSessionHasErrors(['subject', 'message']);
})->with('support portals');
