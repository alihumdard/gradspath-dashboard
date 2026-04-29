<?php

namespace Modules\OfficeHours\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\OfficeHours\app\Services\OfficeHourServiceChoiceService;

class OfficeHourSessionServiceController extends Controller
{
    public function __construct(private readonly OfficeHourServiceChoiceService $choices) {}

    public function update(Request $request, OfficeHourSession $session): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'service_config_id' => ['required', 'integer', 'exists:services_config,id'],
        ]);

        try {
            $session = $this->choices->change($session, Auth::user(), (int) $data['service_config_id']);
        } catch (\RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['office_hours_service' => $exception->getMessage()]);
        }

        $payload = $this->choices->payload($session, Auth::user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Office-hours focus updated.',
                'serviceChoice' => $payload,
            ]);
        }

        return back()->with('success', 'Office-hours focus updated.');
    }
}
