<?php

namespace Modules\Settings\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Institutions\app\Models\University;
use Modules\Settings\app\Http\Requests\UpdateStudentProfileRequest;
use Modules\Settings\app\Models\StudentProfile;
use Modules\Settings\app\Support\AvatarUploadService;
use Modules\Settings\app\Support\TimezoneOptions;

class StudentSettingsController extends Controller
{
    public function __construct(private readonly AvatarUploadService $avatars) {}

    public function index(): View
    {
        $user = Auth::user()->loadMissing('studentProfile.university', 'setting');
        $profile = $user->studentProfile ?? new StudentProfile();

        return view('settings::student.index', [
            'user' => $user,
            'profile' => $profile,
            'universities' => University::query()
                ->where('is_active', true)
                ->orderByRaw('COALESCE(display_name, name)')
                ->get(['id', 'name', 'display_name']),
            'timezoneOptions' => TimezoneOptions::all(),
            'selectedTimezone' => old('timezone', $user->setting?->timezone ?? TimezoneOptions::fallback()),
            'hasSavedTimezone' => filled($user->setting?->timezone),
            'timezoneAutoSaveUrl' => route('settings.timezone.store'),
        ]);
    }

    public function update(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('studentProfile', 'setting');
        $data = $request->validated();
        $avatar = $request->file('avatar');

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'name' => $data['name'],
            ])->save();

            $user->setting()->updateOrCreate([], [
                'theme' => $user->setting?->theme ?? 'light',
                'email_notifications' => $user->setting?->email_notifications ?? true,
                'sms_notifications' => $user->setting?->sms_notifications ?? false,
                'timezone' => $data['timezone'] ?? null,
            ]);

            $profile = $user->studentProfile ?? new StudentProfile([
                'user_id' => $user->id,
            ]);

            $university = isset($data['university_id'])
                ? University::query()->find($data['university_id'])
                : null;

            $institutionText = trim((string) ($data['institution_text'] ?? ''));
            if ($institutionText === '') {
                $institutionText = $university?->display_name ?: $university?->name ?: null;
            }

            $profile->fill([
                'university_id' => $university?->id,
                'institution_text' => $institutionText,
                'program_level' => $data['program_level'] ?? null,
                'program_type' => $data['program_type'] ?? null,
            ]);
            $profile->save();
        });

        if ($avatar) {
            $this->avatars->replaceStudentAvatar($user->fresh(), $avatar);
        }

        return redirect()
            ->route('student.settings.index')
            ->with('success', 'Student profile updated successfully.');
    }
}
