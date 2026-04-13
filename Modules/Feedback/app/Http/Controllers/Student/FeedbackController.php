<?php

namespace Modules\Feedback\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Feedback\app\Http\Requests\StoreFeedbackRequest;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Services\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedback)
    {
    }

    public function index(Request $request): View
    {
        $items = Feedback::query()
            ->with(['student:id,name', 'mentor.user:id,name'])
            ->where('is_visible', true)
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return view('feedback::student.index', [
            'feedbackItems' => $items,
        ]);
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        try {
            $this->feedback->storeStudentFeedback(Auth::user(), $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['feedback' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Feedback submitted successfully.');
    }
}
