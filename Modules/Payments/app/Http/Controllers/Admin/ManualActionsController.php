<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Services\CreditService;
use Modules\Settings\app\Models\Mentor;

class ManualActionsController extends Controller
{
    public function __construct(
        private readonly CreditService $credits,
        private readonly AdminAuditService $audit
    ) {}

    public function adjustCredits(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $admin = Auth::user();
        $target = User::query()->findOrFail((int) $data['user_id']);
        $beforeBalance = (int) ($target->credit?->balance ?? 0);

        try {
            if ((int) $data['amount'] > 0) {
                $wallet = $this->credits->refund($target, (int) $data['amount'], null, $admin, 'Admin adjustment');
            } else {
                $wallet = $this->credits->deduct($target, abs((int) $data['amount']), null, 'Admin adjustment');
            }
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['manual' => $exception->getMessage()]);
        }

        $this->audit->log(
            $admin,
            'manual_credit_adjustment',
            'user_credits',
            $wallet->id,
            ['user_id' => $target->id, 'balance' => $beforeBalance],
            ['user_id' => $target->id, 'amount' => (int) $data['amount'], 'balance' => $wallet->balance],
            $data['notes'] ?? null
        );

        return $this->redirectToManualActions('credits', "Credits adjusted successfully. New balance: {$wallet->balance}.");
    }

    public function amendMentor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mentor_id' => ['required', 'integer', 'exists:mentors,id'],
            'status' => ['required', 'in:pending,active,paused,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $admin = Auth::user();
        $mentor = Mentor::query()->findOrFail((int) $data['mentor_id']);

        $before = $mentor->only(['status']);
        $mentor->status = $data['status'];
        $mentor->save();

        $this->audit->log(
            $admin,
            'amend_mentor',
            'mentors',
            $mentor->id,
            $before,
            ['status' => $mentor->status],
            $data['notes'] ?? null
        );

        return $this->redirectToManualActions('mentor', 'Mentor status updated successfully.');
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('success', $message);
    }
}
