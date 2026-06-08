<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Feedback\app\Models\MentorRating;
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
            'admin_rating_override' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'clear_admin_rating_override' => ['nullable', 'boolean'],
            'admin_rating_override_note' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $admin = Auth::user();
        $mentor = Mentor::query()->with('rating')->findOrFail((int) $data['mentor_id']);
        $rating = $mentor->rating;

        $before = [
            'status' => $mentor->status,
            'admin_rating_override' => $rating?->admin_rating_override !== null ? (float) $rating->admin_rating_override : null,
            'admin_rating_override_note' => $rating?->admin_rating_override_note,
            'admin_rating_overridden_by' => $rating?->admin_rating_overridden_by,
            'admin_rating_overridden_at' => $rating?->admin_rating_overridden_at?->toIso8601String(),
        ];

        DB::transaction(function () use ($mentor, $admin, $data): void {
            $mentor->status = $data['status'];
            $mentor->save();

            $clearOverride = (bool) ($data['clear_admin_rating_override'] ?? false);
            $overrideProvided = array_key_exists('admin_rating_override', $data)
                && $data['admin_rating_override'] !== null
                && $data['admin_rating_override'] !== '';

            if ($clearOverride || $overrideProvided) {
                $rating = MentorRating::query()->firstOrNew(['mentor_id' => $mentor->id]);

                if ($clearOverride) {
                    $rating->forceFill([
                        'admin_rating_override' => null,
                        'admin_rating_override_note' => null,
                        'admin_rating_overridden_by' => null,
                        'admin_rating_overridden_at' => null,
                    ]);
                } else {
                    $rating->forceFill([
                        'admin_rating_override' => round((float) $data['admin_rating_override'], 2),
                        'admin_rating_override_note' => $data['admin_rating_override_note'] ?? null,
                        'admin_rating_overridden_by' => $admin?->id,
                        'admin_rating_overridden_at' => now(),
                    ]);
                }

                $rating->save();
            }
        });

        $mentor->refresh()->load('rating');
        $rating = $mentor->rating;
        $after = [
            'status' => $mentor->status,
            'admin_rating_override' => $rating?->admin_rating_override !== null ? (float) $rating->admin_rating_override : null,
            'admin_rating_override_note' => $rating?->admin_rating_override_note,
            'admin_rating_overridden_by' => $rating?->admin_rating_overridden_by,
            'admin_rating_overridden_at' => $rating?->admin_rating_overridden_at?->toIso8601String(),
        ];

        $this->audit->log(
            $admin,
            'amend_mentor',
            'mentors',
            $mentor->id,
            $before,
            $after,
            $data['notes'] ?? null
        );

        return $this->redirectToManualActions('mentor', 'Mentor status updated successfully.');
    }

    public function updateFeaturedMentors(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mentor_ids' => ['nullable', 'array', 'max:6'],
            'mentor_ids.*' => [
                'integer',
                Rule::exists('mentors', 'id')->where('status', 'active'),
            ],
            'featured_order' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $admin = Auth::user();
        $selectedIds = collect($data['mentor_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $orderedIds = collect(explode(',', (string) ($data['featured_order'] ?? '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn (int $id) => $selectedIds->contains($id))
            ->unique()
            ->values();
        $mentorIds = $orderedIds
            ->merge($selectedIds->reject(fn (int $id) => $orderedIds->contains($id)))
            ->take(6)
            ->values();
        $before = Mentor::query()
            ->where('is_featured', true)
            ->orderByRaw('COALESCE(featured_sort_order, 9999)')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        DB::transaction(function () use ($mentorIds): void {
            Mentor::query()->update([
                'is_featured' => false,
                'featured_sort_order' => null,
            ]);

            $mentorIds->each(function (int $mentorId, int $index): void {
                Mentor::query()
                    ->whereKey($mentorId)
                    ->update([
                        'is_featured' => true,
                        'featured_sort_order' => $index + 1,
                    ]);
            });
        });

        $this->audit->log(
            $admin,
            'update_featured_mentors',
            'mentors',
            null,
            ['featured_mentor_ids' => $before],
            ['featured_mentor_ids' => $mentorIds->all()],
            $data['notes'] ?? null
        );

        $message = $mentorIds->isEmpty()
            ? 'Featured mentors cleared. Dashboard will fall back to top-rated mentors.'
            : 'Featured mentors updated successfully.';

        return $this->redirectToManualActions('mentor', $message);
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('success', $message);
    }
}
