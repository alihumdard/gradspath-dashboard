<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Events\ChatMessageSent;
use Modules\Bookings\app\Mail\BookingChatNotificationMail;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\Chat;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');
});

function makeChatUser(string $prefix): User
{
    return User::factory()->create([
        'email' => $prefix.'-'.Str::uuid().'@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
}

function makeChatMentor(): array
{
    $mentorUser = makeChatUser('mentor');
    $mentorUser->assignRole('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'grad_school_display' => 'Harvard',
        'status' => 'active',
    ]);

    return [$mentor, $mentorUser];
}

function makeChatService(): ServiceConfig
{
    return ServiceConfig::query()->create([
        'service_name' => 'Chat Test Service',
        'service_slug' => 'chat-test-'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 60,
        'price_1on3_per_person' => 50,
        'price_1on3_total' => 150,
        'price_1on5_per_person' => 45,
        'price_1on5_total' => 225,
        'is_office_hours' => false,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 99,
    ]);
}

function makeChatBooking(string $sessionType = '1on1'): array
{
    $student = makeChatUser('student');
    $student->assignRole('student');

    [$mentor, $mentorUser] = makeChatMentor();
    $service = makeChatService();

    $booking = Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => $sessionType,
        'requested_group_size' => $sessionType === '1on3' ? 3 : null,
        'session_at' => now()->addDays(5),
        'session_timezone' => 'Asia/Karachi',
        'duration_minutes' => 60,
        'meeting_link' => 'https://meet.gradspath.test/session/chat-'.$sessionType,
        'meeting_type' => 'zoom',
        'credits_charged' => 1,
        'amount_charged' => 60,
        'currency' => 'USD',
        'pricing_snapshot' => ['service_name' => $service->service_name],
        'status' => 'confirmed',
        'approval_status' => $sessionType === '1on1' ? 'not_required' : 'pending',
        'is_group_payer' => $sessionType !== '1on1',
        'group_payer_id' => $sessionType !== '1on1' ? $student->id : null,
    ]);

    return [$booking, $student, $mentor, $mentorUser];
}

it('lets a student load a booking chat thread and marks received messages as read', function () {
    [$booking, $student, $mentor, $mentorUser] = makeChatBooking();

    Chat::query()->create([
        'booking_id' => $booking->id,
        'sender_id' => $mentorUser->id,
        'receiver_id' => $student->id,
        'message_text' => 'Please share your goals before the session.',
        'is_read' => false,
        'sent_at' => now()->subMinute(),
    ]);

    $this->actingAs($student)
        ->getJson(route('student.bookings.chat.index', $booking->id))
        ->assertOk()
        ->assertJsonPath('bookingId', $booking->id)
        ->assertJsonPath('messages.0.senderId', $mentorUser->id)
        ->assertJsonPath('messages.0.isOwn', false);

    $this->assertDatabaseHas('chats', [
        'booking_id' => $booking->id,
        'receiver_id' => $student->id,
        'is_read' => true,
    ]);
});

it('lets a student send a chat message and broadcasts it', function () {
    Mail::fake();

    [$booking, $student, $mentor, $mentorUser] = makeChatBooking('1on3');

    $this->actingAs($student)
        ->postJson(route('student.bookings.chat.store', $booking->id), [
            'message' => 'I will upload my interview notes tonight.',
        ])
        ->assertCreated()
        ->assertJsonPath('message.bookingId', $booking->id)
        ->assertJsonPath('message.senderId', $student->id)
        ->assertJsonPath('message.receiverId', $mentorUser->id)
        ->assertJsonPath('message.isOwn', true);

    $this->assertDatabaseHas('chats', [
        'booking_id' => $booking->id,
        'sender_id' => $student->id,
        'receiver_id' => $mentorUser->id,
        'message_text' => 'I will upload my interview notes tonight.',
    ]);

    Mail::assertSent(BookingChatNotificationMail::class, function (BookingChatNotificationMail $mail) use ($mentorUser) {
        return $mail->hasTo($mentorUser->email);
    });

    expect(Chat::query()->where('booking_id', $booking->id)->count())->toBe(1);
});

it('lets a mentor load and send messages for their booking thread', function () {
    [$booking, $student, $mentor, $mentorUser] = makeChatBooking();

    Chat::query()->create([
        'booking_id' => $booking->id,
        'sender_id' => $student->id,
        'receiver_id' => $mentorUser->id,
        'message_text' => 'Looking forward to the session.',
        'is_read' => false,
        'sent_at' => now()->subMinute(),
    ]);

    $this->actingAs($mentorUser)
        ->getJson(route('mentor.bookings.chat.index', $booking->id))
        ->assertOk()
        ->assertJsonPath('messages.0.senderId', $student->id)
        ->assertJsonPath('messages.0.isOwn', false);

    $this->actingAs($mentorUser)
        ->postJson(route('mentor.bookings.chat.store', $booking->id), [
            'message' => 'Great, I will review your materials before we meet.',
        ])
        ->assertCreated()
        ->assertJsonPath('message.senderId', $mentorUser->id)
        ->assertJsonPath('message.receiverId', $student->id);

    expect(Chat::query()->where('booking_id', $booking->id)->count())->toBe(2);
});

it('blocks unrelated users from accessing another booking chat', function () {
    [$booking] = makeChatBooking();

    $otherStudent = makeChatUser('other-student');
    $otherStudent->assignRole('student');

    [$otherMentor, $otherMentorUser] = makeChatMentor();

    $this->actingAs($otherStudent)
        ->getJson(route('student.bookings.chat.index', $booking->id))
        ->assertForbidden();

    $this->actingAs($otherMentorUser)
        ->postJson(route('mentor.bookings.chat.store', $booking->id), [
            'message' => 'This should not be allowed.',
        ])
        ->assertForbidden();
});
