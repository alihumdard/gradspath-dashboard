<?php

namespace Modules\Payments\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Payments\app\Http\Requests\PurchaseCreditsRequest;
use Modules\Payments\app\Services\CreditService;

class CreditsController extends Controller
{
    public function __construct(private readonly CreditService $credits)
    {
    }

    public function index(): View
    {
        return view('payments::student.store', [
            'creditBalance' => $this->credits->getBalance(Auth::user()),
        ]);
    }

    public function balance(): RedirectResponse
    {
        return redirect()->route('student.store');
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
}
