<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Institutions\app\Models\FeaturedInstitution;

class LandingPageController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->hasRole('mentor')) {
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('mentor.dashboard');
        }

        if ($user?->hasRole('student')) {
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('student.dashboard');
        }

        $mentorCounts = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->select('program_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('program_type')
            ->pluck('count', 'program_type');

        $therapyPathwayCount = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->whereIn('program_type', ['cmhc', 'msw', 'mft', 'clinical_psy'])
            ->count();

        $disciplinesByType = \Modules\Institutions\app\Models\Discipline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('landing_page.index', [
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
            'programsByType' => \Modules\Institutions\app\Models\UniversityProgram::getLandingPagePrograms(),
            'mentorCounts' => $mentorCounts,
            'therapyPathwayCount' => $therapyPathwayCount,
            'disciplinesByType' => $disciplinesByType,
        ]);
    }

    public function home(): View
    {
        $mentorCounts = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->select('program_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('program_type')
            ->pluck('count', 'program_type');

        $therapyPathwayCount = \Modules\Settings\app\Models\Mentor::query()
            ->where('status', 'active')
            ->whereIn('program_type', ['cmhc', 'msw', 'mft', 'clinical_psy'])
            ->count();

        $disciplinesByType = \Modules\Institutions\app\Models\Discipline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('landing_page.index', [
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
            'programsByType' => \Modules\Institutions\app\Models\UniversityProgram::getLandingPagePrograms(),
            'mentorCounts' => $mentorCounts,
            'therapyPathwayCount' => $therapyPathwayCount,
            'disciplinesByType' => $disciplinesByType,
        ]);
    }

    public function whyUs(): View
    {
        return view('landing_page.why-us');
    }

    public function howItWorks(): View
    {
        return view('landing_page.how-it-works');
    }

    private function getFeaturedInstitutions()
    {
        return FeaturedInstitution::with('university')
            ->orderBy('sort_order')
            ->get()
            ->pluck('university')
            ->filter(fn($u) => $u !== null && !empty($u->logo_url));
    }
}
