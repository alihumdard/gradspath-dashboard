<?php

namespace Modules\Bookings\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Bookings\app\Http\Requests\UpdateBookingOutcomeRequest;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingOutcomeService;

class BookingOutcomeController extends Controller
{
    public function __construct(
        private readonly BookingOutcomeService $outcomes,
        private readonly AdminAuditService $audit,
    ) {}

    public function update(UpdateBookingOutcomeRequest $request): RedirectResponse
    {
        $booking = Booking::query()->findOrFail((int) $request->integer('booking_id'));
        $before = $booking->toArray();
        $updated = $this->outcomes->update($booking, Auth::user(), $request->validated());

        $this->audit->log(
            Auth::user(),
            'manual_booking_outcome_update',
            'bookings',
            $updated->id,
            $before,
            $updated->fresh()->toArray(),
            (string) $request->input('session_outcome_note')
        );

        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', 'bookings')
            ->with('manual_status', [
                'type' => 'success',
                'message' => 'Booking outcome updated successfully.',
            ]);
    }
}
