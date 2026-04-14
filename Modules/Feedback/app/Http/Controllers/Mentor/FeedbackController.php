<?php

namespace Modules\Feedback\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Feedback\app\Models\Feedback;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $items = Feedback::query()
            ->with(['student:id,name', 'mentor.user:id,name'])
            ->where('is_visible', true)
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return view('feedback::mentor.index', [
            'feedbackItems' => $items,
        ]);
    }
}
