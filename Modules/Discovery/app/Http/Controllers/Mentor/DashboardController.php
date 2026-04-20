<?php

namespace Modules\Discovery\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Discovery\app\Services\MentorDiscoveryService;
use Modules\Institutions\app\Models\University;
use Modules\Payments\app\Services\CreditService;
use Modules\Settings\app\Models\Mentor;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MentorDiscoveryService $discovery,
        private readonly CreditService $credits
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $viewerMentor = $user ? Mentor::query()->where('user_id', $user->id)->first() : null;

        return view('discovery::mentor.dashboard', [
            'featuredMentors' => $this->discovery->featured(6, 'mentor', $viewerMentor?->id),
            'creditBalance' => $user ? $this->credits->getBalance($user) : 0,
            'institutions' => University::query()
                ->where('is_active', true)
                ->orderByRaw('COALESCE(display_name, name)')
                ->limit(6)
                ->get(['id', 'name', 'display_name']),
        ]);
    }
}
