<?php

namespace Modules\Bookings\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Events\ChatMessageSent;
use Modules\Bookings\app\Http\Requests\StoreChatMessageRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\Chat;
use Modules\Settings\app\Models\Mentor;
use Throwable;

class BookingChatController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $viewer = Auth::user();
        abort_unless($viewer, 401);

        $booking = $this->findAuthorizedBooking($id, (int) $viewer->getAuthIdentifier());

        Chat::query()
            ->where('booking_id', $booking->id)
            ->where('receiver_id', $viewer->getAuthIdentifier())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Chat::query()
            ->with('sender:id,name')
            ->where('booking_id', $booking->id)
            ->orderBy('sent_at')
            ->get()
            ->map(fn (Chat $chat) => $this->transformMessage($chat, (int) $viewer->getAuthIdentifier()))
            ->all();

        return response()->json([
            'bookingId' => $booking->id,
            'messages' => $messages,
        ]);
    }

    public function store(StoreChatMessageRequest $request, int $id): JsonResponse
    {
        $viewer = Auth::user();
        abort_unless($viewer, 401);

        $viewerId = (int) $viewer->getAuthIdentifier();
        $booking = $this->findAuthorizedBooking($id, $viewerId);
        $receiverId = $this->resolveReceiverId($booking, $viewerId);

        $chat = Chat::query()->create([
            'booking_id' => $booking->id,
            'sender_id' => $viewerId,
            'receiver_id' => $receiverId,
            'message_text' => trim((string) $request->validated()['message']),
            'is_read' => false,
            'sent_at' => now(),
        ])->load('sender:id,name');

        $message = $this->transformMessage($chat, $viewerId);

        try {
            broadcast(new ChatMessageSent($booking->id, $message))->toOthers();
        } catch (Throwable $exception) {
            Log::warning('Booking chat message saved but realtime broadcast failed.', [
                'booking_id' => $booking->id,
                'chat_id' => $chat->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => $message,
        ], 201);
    }

    private function findAuthorizedBooking(int $bookingId, int $viewerId): Booking
    {
        $booking = Booking::query()
            ->with(['mentor:id,user_id', 'chats.sender:id,name'])
            ->findOrFail($bookingId);

        if ((int) $booking->student_id === $viewerId) {
            return $booking;
        }

        $mentor = Mentor::query()->where('user_id', $viewerId)->first();

        if ($mentor && (int) $booking->mentor_id === (int) $mentor->id) {
            return $booking;
        }

        abort(403);
    }

    private function resolveReceiverId(Booking $booking, int $viewerId): int
    {
        if ((int) $booking->student_id === $viewerId) {
            return (int) ($booking->mentor?->user_id ?? 0);
        }

        return (int) $booking->student_id;
    }

    private function transformMessage(Chat $chat, int $viewerId): array
    {
        return [
            'id' => $chat->id,
            'bookingId' => (int) $chat->booking_id,
            'senderId' => (int) $chat->sender_id,
            'senderName' => $chat->sender?->name ?? 'User',
            'receiverId' => (int) $chat->receiver_id,
            'messageText' => $chat->message_text,
            'sentAt' => optional($chat->sent_at)->toIso8601String(),
            'isRead' => (bool) $chat->is_read,
            'isOwn' => (int) $chat->sender_id === $viewerId,
        ];
    }
}
