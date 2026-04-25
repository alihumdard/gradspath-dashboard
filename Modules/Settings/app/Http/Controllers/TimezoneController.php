<?php

namespace Modules\Settings\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\app\Support\TimezoneOptions;

class TimezoneController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'timezone' => ['required', 'string'],
        ]);

        $timezone = (string) $data['timezone'];

        if (!TimezoneOptions::isSupported($timezone)) {
            return response()->json([
                'saved' => false,
                'timezone' => TimezoneOptions::fallback(),
            ], 422);
        }

        $user = $request->user();
        $setting = $user->setting()->firstOrCreate([], [
            'theme' => 'light',
            'email_notifications' => true,
            'sms_notifications' => false,
            'timezone' => null,
        ]);

        if ($setting->timezone === null || $setting->timezone === '') {
            $setting->forceFill(['timezone' => $timezone])->save();
        }

        return response()->json([
            'saved' => true,
            'timezone' => TimezoneOptions::preferredFor($user->fresh('setting')),
        ]);
    }
}
