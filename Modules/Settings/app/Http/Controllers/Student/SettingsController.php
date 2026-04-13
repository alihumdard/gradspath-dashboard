<?php

namespace Modules\Settings\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Settings\app\Http\Requests\UpdateSettingsRequest;
use Modules\Settings\app\Models\UserSetting;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = UserSetting::query()->firstOrCreate(
            ['user_id' => Auth::id()],
            ['theme' => 'light', 'email_notifications' => true, 'sms_notifications' => false]
        );

        return view('settings::student.index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $settings = UserSetting::query()->firstOrCreate(
            ['user_id' => Auth::id()],
            ['theme' => 'light', 'email_notifications' => true, 'sms_notifications' => false]
        );

        $settings->update($request->validated());

        return back()->with('success', 'Settings updated successfully.');
    }
}
