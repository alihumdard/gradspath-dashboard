<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Mail\BookingChatNotificationMail;
use Modules\Bookings\app\Models\Chat;

class SendBookingChatNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $chatId) {}

    public function handle(): void
    {
        $chat = Chat::query()->with(['sender', 'receiver', 'booking.service'])->find($this->chatId);

        if (!$chat || !$chat->receiver?->email || !$chat->booking) {
            return;
        }

        $sessionAt = $chat->booking->sessionAtInTimezone();

        Mail::to($chat->receiver->email)->send(new BookingChatNotificationMail(
            [
                'booking_id' => $chat->booking->id,
                'sender_name' => $chat->sender?->name ?? 'A participant',
                'service_name' => $chat->booking->service?->service_name ?? 'Service',
                'session_date' => $sessionAt?->format('l, F j, Y') ?? 'TBD',
                'session_time' => $sessionAt?->format('g:i A') ?? 'TBD',
                'session_timezone' => $chat->booking->session_timezone,
                'message_preview' => $chat->message_text,
            ],
            $chat->receiver->name ?? 'Participant',
        ));
    }
}
