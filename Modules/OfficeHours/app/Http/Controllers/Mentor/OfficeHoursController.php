<?php

namespace Modules\OfficeHours\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Bookings\app\Services\BookingAvailabilityService;

class OfficeHoursController extends Controller
{
    public function __construct(private readonly BookingAvailabilityService $availability) {}

    public function index(): View
    {
        $officeHoursData = collect($this->availability->officeHoursDirectoryMentors())
            ->map(function (array $mentor): array {
                $mentor['bookingUrl'] = route('mentor.book-mentor', $mentor['id']);

                return $mentor;
            })
            ->values()
            ->all();

        return view('office-hours::mentor.schedules', [
            'officeHoursData' => $officeHoursData,
        ]);
    }
}
