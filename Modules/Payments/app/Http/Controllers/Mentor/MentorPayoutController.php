<?php

namespace Modules\Payments\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Payments\app\Services\StripeConnectService;

class MentorPayoutController extends Controller
{
    public function __construct(private readonly StripeConnectService $connect) {}

    public function connect(Request $request): RedirectResponse
    {
        return redirect()->away(
            $this->connectUrl($request)
        );
    }

    public function refresh(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', Rule::in(['account_onboarding', 'account_update'])],
        ]);

        return redirect()->away(
            $this->connectUrl($request, $data['action'] ?? null)
        );
    }

    public function return(): RedirectResponse
    {
        return redirect()
            ->route('mentor.settings.index', ['stripe_return' => 1])
            ->with('success', 'Stripe onboarding was opened. We are syncing your payout status now.');
    }

    public function status(Request $request): JsonResponse
    {
        $mentor = $request->user()?->loadMissing('mentor')->mentor;

        if (!$mentor) {
            return response()->json($this->connect->emptyPayoutStatus());
        }

        return response()->json($this->connect->payoutStatus($mentor));
    }

    private function connectUrl(Request $request, ?string $type = null): string
    {
        $user = $request->user()->loadMissing('mentor');
        $mentor = $this->connect->ensureConnectedAccount($user);
        $action = $type ?? $this->connect->defaultLinkType($mentor);

        $link = $this->connect->createHostedOnboardingLink(
            $mentor,
            route('mentor.payouts.refresh', ['action' => $action]),
            route('mentor.payouts.return'),
            $action
        );

        return (string) Arr::get($link, 'url');
    }
}
