<?php

namespace Modules\Discovery\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Discovery\app\Services\MentorDiscoveryService;
use Modules\Discovery\app\Services\TopInstitutionService;
use Modules\Payments\app\Services\CreditService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MentorDiscoveryService $discovery,
        private readonly TopInstitutionService $topInstitutions,
        private readonly CreditService $credits
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        return view('discovery::student.dashboard', [
            'portalRole' => 'student',
            'featuredMentors' => $this->discovery->featured(),
            'creditBalance' => $user ? $this->credits->getBalance($user) : 0,
            'institutions' => $this->topInstitutions->forDashboard(),
        ]);
    }
}
