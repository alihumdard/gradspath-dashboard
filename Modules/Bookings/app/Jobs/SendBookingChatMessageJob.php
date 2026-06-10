<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Mail\BookingChatMessageMail;
use Modules\Bookings\app\Models\Chat;

class SendBookingChatMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $chatId) {}

    public function handle(): void
    {
        $chat = Chat::query()
            ->with(['booking.mentor.user', 'booking.service', 'receiver', 'sender'])
            ->find($this->chatId);

        if (!$chat?->receiver?->email || !$chat->booking) {
            return;
        }

        Mail::to($chat->receiver->email)->send(new BookingChatMessageMail($chat, $this->chatUrl($chat)));
    }

    private function chatUrl(Chat $chat): string
    {
        $receiver = $chat->receiver;

        if ($receiver?->hasRole('mentor')) {
            return route('mentor.bookings.index');
        }

        return route('student.bookings.index');
    }
}
