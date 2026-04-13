<?php

namespace Modules\OfficeHours\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficeHoursController extends Controller
{
    public function studentIndex(): View|RedirectResponse
    {
        if (Auth::user()?->hasRole('mentor')) {
            return redirect()->route('mentor.office-hours');
        }

        return view('office-hours::student.index');
    }

    public function mentorIndex(): View
    {
        return view('office-hours::mentor.schedules');
    }
}
