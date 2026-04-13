<?php

namespace Modules\Discovery\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        $mentors = $this->discovery->search($filters);

        return view('discovery::student.explore', [
            'mentors' => $mentors,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $mentor = Mentor::query()
            ->with(['user:id,name,email,avatar_url', 'university', 'rating', 'services'])
            ->findOrFail($id);

        return view('discovery::student.mentor-profile', [
            'mentor' => [
                'id' => $mentor->id,
                'name' => $mentor->user?->name ?? 'Mentor',
                'role' => $mentor->title ?: ucfirst((string) ($mentor->program_type ?? $mentor->mentor_type ?? 'mentor')),
                'bio' => $mentor->bio ?: 'Mentor biography',
            ],
        ]);
    }
}
