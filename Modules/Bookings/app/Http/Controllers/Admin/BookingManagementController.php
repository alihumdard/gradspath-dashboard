<?php

namespace Modules\Bookings\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Bookings\app\Exceptions\BookingException;
use Modules\Bookings\app\Http\Requests\AdminCancelBookingRequest;
use Modules\Bookings\app\Http\Requests\AdminUpdateBookingRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingService;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingManagementController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly AdminAuditService $audit,
    ) {}

    public function related(string $entityType, int $entityId): JsonResponse
    {
        [$entityLabel, $query] = $this->resolveEntityQuery($entityType, $entityId);

        $bookings = $query
            ->with([
                'booker:id,name,email',
                'mentor.user:id,name,email',
                'service:id,service_name',
            ])
            ->orderByDesc('session_at')
            ->get();

        return response()->json([
            'entity' => [
                'type' => $entityType,
                'id' => $entityId,
                'label' => $entityLabel,
            ],
            'options' => $this->options(),
            'bookings' => $bookings->map(fn (Booking $booking) => $this->transformBooking($booking))->values()->all(),
        ]);
    }

    public function update(AdminUpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        if (in_array((string) $booking->status, ['cancelled', 'cancelled_pending_refund'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Cancelled bookings cannot be edited.',
            ]);
        }

        $data = $request->validated();
        $before = $booking->toArray();
        $sessionAtUtc = Carbon::parse($data['session_at'], $data['session_timezone'])->utc();

        $booking->fill([
            'session_at' => $sessionAtUtc->toDateTimeString(),
            'session_timezone' => $data['session_timezone'],
            'duration_minutes' => (int) $data['duration_minutes'],
            'meeting_link' => $data['meeting_link'] ?: null,
            'meeting_type' => $data['meeting_type'],
            'status' => $data['status'],
            'approval_status' => $data['approval_status'],
            'session_outcome' => $data['session_outcome'],
            'completion_source' => $data['completion_source'] ?: null,
            'session_outcome_note' => $data['session_outcome_note'] ?: null,
        ]);

        if ((string) $booking->status === 'completed' && $booking->completed_at === null) {
            $booking->completed_at = $booking->scheduledEndAt();
        }

        $booking->save();

        $this->audit->log(
            Auth::user(),
            'admin_booking_update',
            'bookings',
            $booking->id,
            $before,
            $booking->fresh()->toArray(),
            $data['admin_note']
        );

        return response()->json([
            'message' => 'Booking updated successfully.',
            'booking' => $this->transformBooking($booking->fresh([
                'booker:id,name,email',
                'mentor.user:id,name,email',
                'service:id,service_name',
            ])),
        ]);
    }

    public function destroy(AdminCancelBookingRequest $request, Booking $booking): JsonResponse
    {
        $before = $booking->toArray();

        try {
            $cancelled = $this->bookings->cancelBooking($booking, Auth::user(), $request->validated()['reason']);
        } catch (BookingException $exception) {
            throw ValidationException::withMessages([
                'reason' => $exception->getMessage(),
            ]);
        }

        $this->audit->log(
            Auth::user(),
            'admin_booking_cancel',
            'bookings',
            $cancelled->id,
            $before,
            $cancelled->fresh()->toArray(),
            $request->validated()['reason']
        );

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'booking' => $this->transformBooking($cancelled->fresh([
                'booker:id,name,email',
                'mentor.user:id,name,email',
                'service:id,service_name',
            ])),
        ]);
    }

    private function resolveEntityQuery(string $entityType, int $entityId): array
    {
        return match ($entityType) {
            'user' => [
                User::query()->findOrFail($entityId)->name ?: "User #{$entityId}",
                Booking::query()->where('student_id', $entityId)->whereNotIn('status', ['cancelled', 'cancelled_pending_refund']),
            ],
            'mentor' => [
                Mentor::query()->with('user:id,name')->findOrFail($entityId)->user?->name ?: "Mentor #{$entityId}",
                Booking::query()->where('mentor_id', $entityId)->whereNotIn('status', ['cancelled', 'cancelled_pending_refund']),
            ],
            'service' => [
                ServiceConfig::query()->findOrFail($entityId)->service_name ?: "Service #{$entityId}",
                Booking::query()->where('service_config_id', $entityId)->whereNotIn('status', ['cancelled', 'cancelled_pending_refund']),
            ],
            default => abort(404),
        };
    }

    private function options(): array
    {
        return [
            'statuses' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'completed' => 'Completed',
                'no_show' => 'No Show',
            ],
            'approval_statuses' => [
                'not_required' => 'Not required',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
            'meeting_types' => [
                'zoom' => 'Zoom',
                'google_meet' => 'Google Meet',
            ],
            'session_outcomes' => [
                'completed' => 'Completed',
                'no_show_student' => 'Student no-show',
                'no_show_mentor' => 'Mentor no-show',
                'interrupted' => 'Interrupted',
                'ended_early' => 'Ended early',
                'unknown' => 'Unknown',
            ],
            'completion_sources' => [
                'schedule' => 'Schedule',
                'zoom_event' => 'Zoom event',
                'manual' => 'Manual',
            ],
        ];
    }

    private function transformBooking(Booking $booking): array
    {
        $sessionAt = $booking->sessionAtInTimezone($booking->session_timezone);

        return [
            'id' => $booking->id,
            'student_name' => $booking->booker?->name ?: 'Unknown student',
            'mentor_name' => $booking->mentor?->user?->name ?: 'Unknown mentor',
            'service_name' => $booking->service?->service_name ?: 'Unknown service',
            'session_at_display' => $sessionAt?->format('M j, Y g:i A') ?: '-',
            'session_at_input' => $sessionAt?->format('Y-m-d\\TH:i'),
            'session_timezone' => $booking->session_timezone ?: config('app.timezone', 'UTC'),
            'duration_minutes' => (int) $booking->duration_minutes,
            'meeting_link' => $booking->meeting_link,
            'meeting_type' => $booking->meeting_type ?: 'zoom',
            'status' => $booking->status,
            'status_label' => $this->labelize($booking->status),
            'approval_status' => $booking->approval_status ?: 'not_required',
            'session_outcome' => $booking->session_outcome ?: 'completed',
            'session_outcome_label' => $this->labelize($booking->session_outcome ?: 'completed'),
            'completion_source' => $booking->completion_source ?: 'manual',
            'session_outcome_note' => $booking->session_outcome_note ?: '',
            'can_cancel' => in_array((string) $booking->status, ['pending', 'confirmed'], true)
                && $booking->session_at?->isFuture(),
            'can_edit' => ! in_array((string) $booking->status, ['cancelled', 'cancelled_pending_refund'], true),
        ];
    }

    private function labelize(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '-';
        }

        return collect(explode('_', $value))
            ->map(fn (string $segment) => ucfirst($segment))
            ->implode(' ');
    }
}
