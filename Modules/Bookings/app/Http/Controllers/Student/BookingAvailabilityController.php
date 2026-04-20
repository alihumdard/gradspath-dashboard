<?php

namespace Modules\Bookings\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Bookings\app\Services\BookingAvailabilityService;

class BookingAvailabilityController extends Controller
{
    public function __construct(private readonly BookingAvailabilityService $availability) {}

    public function months(Request $request): JsonResponse
    {
        return response()->json([
            'months' => $this->availability->availableMonths(
                (int) $request->integer('mentor_id'),
                (int) $request->integer('service_config_id'),
                (string) $request->string('session_type')
            ),
        ]);
    }

    public function days(Request $request): JsonResponse
    {
        return response()->json(
            $this->availability->availableDays(
                (int) $request->integer('mentor_id'),
                (int) $request->integer('service_config_id'),
                (string) $request->string('session_type'),
                (string) $request->string('month')
            )
        );
    }

    public function times(Request $request): JsonResponse
    {
        return response()->json(
            $this->availability->availableTimes(
                (int) $request->integer('mentor_id'),
                (int) $request->integer('service_config_id'),
                (string) $request->string('session_type'),
                (string) $request->string('date')
            )
        );
    }
}
