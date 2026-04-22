<?php

namespace Modules\Feedback\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Feedback\app\Http\Requests\AmendFeedbackRequest;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Services\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedback,
        private readonly AdminAuditService $audit
    ) {}

    public function update(AmendFeedbackRequest $request, ?int $id = null): RedirectResponse
    {
        $feedbackId = $id ?? (int) $request->integer('feedback_id');
        $before = Feedback::query()->findOrFail($feedbackId)->toArray();
        $feedback = $this->feedback->moderate($feedbackId, Auth::user(), $request->validated());

        $this->audit->log(
            Auth::user(),
            'manual_feedback_update',
            'feedback',
            $feedback->id,
            $before,
            $feedback->fresh()->toArray(),
            (string) $request->input('admin_note')
        );

        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', 'feedback')
            ->with('manual_status', [
                'type' => 'success',
                'message' => 'Feedback moderated successfully.',
            ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $feedback = Feedback::query()->findOrFail($id);
        $feedback->is_visible = false;
        $feedback->save();

        return back()->with('success', 'Feedback hidden successfully.');
    }
}
