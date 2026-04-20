<?php

namespace Modules\Payments\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Bookings\app\Http\Requests\CreateBookingRequest;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Services\BookingCheckoutService;

class BookingCheckoutController extends Controller
{
    public function __construct(private readonly BookingCheckoutService $checkout) {}

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['portal_context'] = $this->portalContext();

        try {
            $payment = $this->checkout->createCheckoutSession(Auth::user(), $data);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'checkout_url' => $payment->checkout_url,
            'session_id' => $payment->stripe_checkout_session_id,
        ]);
    }

    public function success(): RedirectResponse
    {
        $sessionId = (string) request()->query('session_id', '');

        abort_if($sessionId === '', 404);

        try {
            $booking = $this->checkout->completeSuccessfulCheckout(Auth::user(), $sessionId);
        } catch (\Throwable $exception) {
            $payment = BookingPayment::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
            $portal = (string) data_get($payment?->request_payload, 'portal_context', $this->portalContext());

            if ($payment?->mentor_id) {
                return redirect()
                    ->route("{$portal}.mentor.book", ['id' => $payment->mentor_id])
                    ->withErrors(['booking' => $exception->getMessage()]);
            }

            return redirect()
                ->route("{$portal}.bookings.index")
                ->withErrors(['booking' => $exception->getMessage()]);
        }

        return redirect()
            ->route("{$this->portalContext()}.bookings.show", $booking->id)
            ->with('success', 'Payment successful. Your booking has been confirmed.');
    }

    private function portalContext(): string
    {
        $routeName = (string) request()->route()?->getName();

        return str_starts_with($routeName, 'mentor.') ? 'mentor' : 'student';
    }
}
