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

        return view('landing_page.index', [
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
        ]);
    }

    public function home(): View
    {
        return view('landing_page.index', [
            'featuredInstitutions' => $this->getFeaturedInstitutions(),
        ]);
    }

    public function whyUs(): View
    {
        return view('landing_page.why-us');
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
