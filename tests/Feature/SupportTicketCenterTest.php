<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Models\SupportTicket;
use Modules\Support\app\Services\SupportTicketService;
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
        ->assertSee('My Tickets')
        ->assertSee('No support tickets yet.');
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
    expect($ticket->ticket_ref)->toBe('SUP-00001');
    expect($ticket->status)->toBe('open');
})->with('support portals');

it('shows the signed in user their ticket history with admin replies', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);
    $admin = makeSupportUser('admin', 'support-admin');

    $ticket = app(SupportTicketService::class)->create($user, [
        'subject' => 'Payment question',
        'message' => 'Can you confirm my booking payment?',
    ]);

    app(SupportTicketService::class)->reply(
        $ticket,
        $admin,
        'We confirmed your payment and updated the booking.',
        'resolved'
    );

    $this->actingAs($user)
        ->get(route($indexRoute))
        ->assertOk()
        ->assertSee('My Tickets')
        ->assertSee('SUP-00001')
        ->assertSee('Payment question')
        ->assertSee('Can you confirm my booking payment?')
        ->assertSee('We confirmed your payment and updated the booking.')
        ->assertSee('Resolved');
})->with('support portals');

it('keeps ticket history scoped to the signed in user', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);
    $other = makeSupportUser($role, 'other-'.$role);

    app(SupportTicketService::class)->create($user, [
        'subject' => 'My visible ticket',
        'message' => 'This should be visible.',
    ]);

    app(SupportTicketService::class)->create($other, [
        'subject' => 'Other hidden ticket',
        'message' => 'This should not be visible.',
    ]);

    $this->actingAs($user)
        ->get(route($indexRoute))
        ->assertOk()
        ->assertSee('My visible ticket')
        ->assertDontSee('Other hidden ticket');
})->with('support portals');

it('sanitizes ticket messages and stores the raw audit copy', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role);

    $this->from(route($indexRoute))
        ->actingAs($user)
        ->post(route($storeRoute), [
            'subject' => 'Unsafe message',
            'message' => '<strong>Hello</strong><script>alert("x")</script>',
        ])
        ->assertRedirect(route($indexRoute));

    $ticket = SupportTicket::query()->where('user_id', $user->id)->firstOrFail();

    expect($ticket->message)->toBe('Helloalert("x")')
        ->and($ticket->message_raw)->toBe('<strong>Hello</strong><script>alert("x")</script>');
})->with('support portals');

it('rate limits support ticket submission', function (string $role, string $indexRoute, string $storeRoute) {
    $user = makeSupportUser($role, $role.'-limited');

    for ($i = 1; $i <= 5; $i++) {
        $this->from(route($indexRoute))
            ->actingAs($user)
            ->post(route($storeRoute), [
                'subject' => 'Ticket '.$i,
                'message' => 'Support message '.$i,
            ])
            ->assertRedirect(route($indexRoute));
    }

    $this->actingAs($user)
        ->post(route($storeRoute), [
            'subject' => 'Ticket 6',
            'message' => 'Support message 6',
        ])
        ->assertTooManyRequests();
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
