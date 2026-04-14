<?php

namespace Modules\OfficeHours\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OfficeHoursController extends Controller
{
    public function index(): View
    {
        return view('office-hours::mentor.schedules');
    }
}
