<?php

namespace Modules\Discovery\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Discovery\app\Http\Requests\SearchMentorsRequest;
use Modules\Discovery\app\Services\MentorDiscoveryService;
use Modules\Settings\app\Models\Mentor;

class MentorSearchController extends Controller
{
    public function __construct(private readonly MentorDiscoveryService $discovery) {}

    public function index(SearchMentorsRequest $request): View
    {
        $filters = $request->validated();
        $viewerMentor = Mentor::query()->where('user_id', Auth::id())->first();

        return view('discovery::mentor.explore', [
            'mentors' => $this->discovery->search($filters),
            'mentorsData' => $this->discovery->browseData('mentor', $viewerMentor?->id),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $viewerMentor = Mentor::query()->where('user_id', Auth::id())->first();
        $mentor = Mentor::query()
            ->with(['user:id,name,email,avatar_url', 'university', 'rating', 'services'])
            ->findOrFail($id);

        return view('discovery::mentor.mentor-profile', [
            'mentor' => [
                'id' => $mentor->id,
                'name' => $mentor->user?->name ?? 'Mentor',
                'role' => $mentor->title ?: ucfirst((string) ($mentor->program_type ?? $mentor->mentor_type ?? 'mentor')),
                'bio' => $mentor->bio ?: 'Mentor biography',
                'canBook' => !$viewerMentor || (int) $viewerMentor->id !== (int) $mentor->id,
                'bookingUrl' => route('mentor.mentor.book', $mentor->id),
            ],
        ]);
    }
}
