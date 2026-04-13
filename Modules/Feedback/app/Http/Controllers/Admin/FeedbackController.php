<?php

namespace Modules\Feedback\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Feedback\app\Http\Requests\AmendFeedbackRequest;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Services\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedback) {}

    public function update(AmendFeedbackRequest $request, int $id): RedirectResponse
    {
        $this->feedback->moderate($id, Auth::user(), $request->validated());

        return back()->with('success', 'Feedback moderated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $feedback = Feedback::query()->findOrFail($id);
        $feedback->is_visible = false;
        $feedback->save();

        return back()->with('success', 'Feedback hidden successfully.');
    }
}
