<?php

namespace Modules\Bookings\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $bookingId,
        public array $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('booking.'.$this->bookingId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'bookingId' => $this->bookingId,
            'message' => $this->message,
        ];
    }
}
