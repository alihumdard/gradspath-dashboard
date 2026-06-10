<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Jobs\NotifyAdminNewTicketJob;
use Modules\Support\app\Jobs\SendUserTicketConfirmationJob;
use Modules\Support\app\Jobs\SendUserTicketReplyJob;
use Modules\Support\app\Mail\AdminNewSupportTicketMail;
use Modules\Support\app\Mail\UserSupportTicketConfirmationMail;
use Modules\Support\app\Mail\UserSupportTicketReplyMail;
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

it('routes logged in website support visitors to their portal support tab', function () {
    $this->get(route('public.support'))
        ->assertOk()
        ->assertSee('Contact Support');

    $student = makeSupportUser('student', 'support-student');
    $mentor = makeSupportUser('mentor', 'support-mentor');
    $admin = makeSupportUser('admin', 'support-admin');

    $this->actingAs($student)
        ->get(route('public.support'))
        ->assertRedirect(route('student.support.index'));

    $this->actingAs($mentor)
        ->get(route('public.support'))
        ->assertRedirect(route('mentor.support.index'));

    $this->actingAs($admin)
        ->get(route('public.support'))
        ->assertRedirect(route('admin.support.tickets.index'));
});

it('shows portal support access instead of login prompts on the landing contact section for signed in users', function () {
    $student = makeSupportUser('student', 'support-student');

    $this->actingAs($student)
        ->get('/home')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Logout')
        ->assertSee('Open your support tickets')
        ->assertSee('Open Support')
        ->assertDontSee('Sign in to submit feedback');
});

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

    Queue::assertPushed(NotifyAdminNewTicketJob::class, fn ($job) => $job->ticketId === $ticket->id);
    Queue::assertPushed(SendUserTicketConfirmationJob::class, fn ($job) => $job->ticketId === $ticket->id);
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

it('lets admins view user tickets and reply with a status update', function () {
    $student = makeSupportUser('student', 'support-student');
    $admin = makeSupportUser('admin', 'support-admin');

    $ticket = app(SupportTicketService::class)->create($student, [
        'subject' => 'Cannot access booking',
        'message' => 'The meeting link is not opening.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.support.tickets.index'))
        ->assertOk()
        ->assertSee('Support Tickets')
        ->assertSee('nav-badge', false)
        ->assertSee($ticket->ticket_ref)
        ->assertSee('Cannot access booking')
        ->assertSee($student->email)
        ->assertDontSee('Save Reply');

    $this->actingAs($admin)
        ->get(route('admin.support.tickets.show', $ticket->id))
        ->assertOk()
        ->assertSee('Save Reply');

    $this->from(route('admin.support.tickets.show', $ticket->id))
        ->actingAs($admin)
        ->patch(route('admin.support.tickets.update', $ticket->id), [
            'admin_reply' => 'We refreshed the meeting link. Please try again.',
            'status' => 'resolved',
        ])
        ->assertRedirect(route('admin.support.tickets.show', $ticket->id))
        ->assertSessionHas('success', 'Support ticket updated successfully.');

    $ticket->refresh();

    expect($ticket->status)->toBe('resolved')
        ->and($ticket->admin_reply)->toBe('We refreshed the meeting link. Please try again.')
        ->and($ticket->handled_by)->toBe($admin->id)
        ->and($ticket->replied_at)->not->toBeNull();

    Queue::assertPushed(SendUserTicketReplyJob::class, fn ($job) => $job->ticketId === $ticket->id);
});

it('lets admins update ticket status without replacing the current reply', function () {
    $student = makeSupportUser('student', 'support-student');
    $admin = makeSupportUser('admin', 'support-admin');

    $ticket = app(SupportTicketService::class)->create($student, [
        'subject' => 'Payment proof',
        'message' => 'Do you need anything else from me?',
    ]);

    app(SupportTicketService::class)->reply($ticket, $admin, 'Please upload the payment receipt.', 'more_information_required');
    $ticket->refresh();
    $originalReplyTime = $ticket->replied_at;
    Queue::fake();

    $this->actingAs($admin)
        ->patch(route('admin.support.tickets.update', $ticket->id), [
            'admin_reply' => '',
            'status' => 'pending',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Support ticket updated successfully.');

    $ticket->refresh();

    expect($ticket->status)->toBe('pending')
        ->and($ticket->admin_reply)->toBe('Please upload the payment receipt.')
        ->and($ticket->replied_at?->toIso8601String())->toBe($originalReplyTime?->toIso8601String());

    Queue::assertNotPushed(SendUserTicketReplyJob::class);
});

it('sends support ticket notification emails from queued jobs', function () {
    Mail::fake();

    $student = makeSupportUser('student', 'support-student');
    $admin = makeSupportUser('admin', 'support-admin');

    $ticket = SupportTicket::query()->create([
        'user_id' => $student->id,
        'ticket_ref' => 'SUP-00099',
        'subject' => 'Need help',
        'message' => 'Please check my account.',
        'message_raw' => 'Please check my account.',
        'status' => 'open',
        'admin_reply' => 'We checked this for you.',
        'replied_at' => now(),
    ]);

    (new NotifyAdminNewTicketJob($ticket->id))->handle();
    (new SendUserTicketConfirmationJob($ticket->id))->handle();
    (new SendUserTicketReplyJob($ticket->id))->handle();

    Mail::assertSent(AdminNewSupportTicketMail::class, fn ($mail) => $mail->hasTo($admin->email));
    Mail::assertSent(UserSupportTicketConfirmationMail::class, fn ($mail) => $mail->hasTo($student->email));
    Mail::assertSent(UserSupportTicketReplyMail::class, fn ($mail) => $mail->hasTo($student->email));
});
