<?php

namespace Modules\Discovery\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Discovery\app\Services\AdminMentorsTableService;
use Modules\Discovery\app\Services\AdminOverviewService;
use Modules\Discovery\app\Services\AdminRankingsService;
use Modules\Discovery\app\Services\AdminRevenueService;
use Modules\Discovery\app\Services\AdminServicesTableService;
use Modules\Discovery\app\Services\AdminUsersTableService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminOverviewService $overviewData,
        private readonly AdminUsersTableService $users,
        private readonly AdminMentorsTableService $mentors,
        private readonly AdminServicesTableService $servicesTable,
        private readonly AdminRankingsService $rankings,
        private readonly AdminRevenueService $revenue,
    ) {}

    public function dashboard(): RedirectResponse
    {
        return redirect()->route('admin.overview');
    }

    public function overview(): View
    {
        return view('discovery::admin.overview', [
            'adminOverviewData' => $this->overviewData->build(),
        ]);
    }

    public function users(): View
    {
        return view('discovery::admin.users', [
            'adminUsersData' => $this->users->build(),
        ]);
    }

    public function mentors(): View
    {
        return view('discovery::admin.mentors', [
            'adminMentorsData' => $this->mentors->build(),
        ]);
    }

    public function services(): View
    {
        return view('discovery::admin.services', [
            'adminServiceRows' => $this->servicesTable->build(),
        ]);
    }

    public function revenue(Request $request): View
    {
        $validated = $request->validate([
            'revenue_range' => ['nullable', Rule::in(['30d', '60d', '6m', '12m', 'all'])],
        ]);

        return view('discovery::admin.revenue', [
            'adminRevenueData' => $this->revenue->build((string) ($validated['revenue_range'] ?? '30d')),
        ]);
    }

    public function rankings(): View
    {
        return view('discovery::admin.rankings', [
            'adminRankingsData' => $this->rankings->build(),
        ]);
    }

    public function manualActions(): View
    {
        return view('discovery::admin.manual-actions');
    }
}
