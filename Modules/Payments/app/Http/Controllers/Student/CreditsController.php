<?php

namespace Modules\Payments\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Payments\app\Http\Requests\PurchaseCreditsRequest;
use Modules\Payments\app\Services\CreditCheckoutService;
use Modules\Payments\app\Services\CreditService;

class CreditsController extends Controller
{
    public function __construct(
        private readonly CreditCheckoutService $creditCheckout,
        private readonly CreditService $credits
    ) {}

    public function index(): View
    {
        return view('payments::student.store', [
            'creditBalance' => $this->credits->getBalance(Auth::user()),
            'creditPackPrice' => 200,
            'creditPackCredits' => 5,
        ]);
    }

    public function balance(): JsonResponse
    {
        return response()->json([
            'balance' => $this->credits->getBalance(Auth::user()),
        ]);
    }

    public function purchase(PurchaseCreditsRequest $request): RedirectResponse
    {
        try {
            $wallet = $this->credits->purchase(
                Auth::user(),
                (int) $request->validated()['credits'],
                $request->validated()['stripe_payment_id'] ?? null,
                null
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', "Credits purchased successfully. New balance: {$wallet->balance}.");
    }

    public function checkout(PurchaseCreditsRequest $request): JsonResponse
    {
        try {
            $session = $this->creditCheckout->createCheckoutSession(
                Auth::user(),
                (int) $request->validated()['credits'],
                $request->validated()['office_hours_program'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'checkout_url' => (string) ($session['url'] ?? ''),
            'session_id' => (string) ($session['id'] ?? ''),
        ]);
    }

    public function success(): RedirectResponse
    {
        $sessionId = (string) request()->query('session_id', '');
        abort_if($sessionId === '', 404);

        try {
            $result = $this->creditCheckout->completeSuccessfulCheckout(Auth::user(), $sessionId);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('student.store')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('student.store')
            ->with('success', "Payment successful. {$result['credits']} credits were added to your balance.");
    }
}
