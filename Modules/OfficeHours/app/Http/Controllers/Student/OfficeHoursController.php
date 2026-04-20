<?php

namespace Modules\OfficeHours\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Bookings\app\Services\BookingAvailabilityService;

class OfficeHoursController extends Controller
{
    public function __construct(private readonly BookingAvailabilityService $availability) {}

    public function index(): View
    {
        return view('office-hours::student.index', [
            'officeHoursData' => $this->availability->officeHoursDirectoryMentors(),
        ]);
    }
}
