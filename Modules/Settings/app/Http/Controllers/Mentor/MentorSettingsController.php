<?php

namespace Modules\Settings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Bookings\app\Services\ZoomService;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Modules\Settings\app\Http\Requests\UpdateMentorSettingsRequest;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Support\AvatarUploadService;
use Modules\Settings\app\Support\TimezoneOptions;

class MentorSettingsController extends Controller
{
    public function __construct(
        private readonly ZoomService $zoom,
        private readonly AvatarUploadService $avatars,
    ) {}

    public function index(): View
    {
        $user = Auth::user()->loadMissing('mentor.services', 'mentor.university', 'setting');
        $mentor = $user->mentor ?? new Mentor([
            'mentor_type' => 'graduate',
        ]);
        $selectedUniversityId = old('university_id', $mentor->university_id);
        $selectedUniversity = $selectedUniversityId
            ? University::query()
                ->where('is_active', true)
                ->find((int) $selectedUniversityId)
            : null;
        $universityPrograms = $this->universityProgramsForUniversityId($selectedUniversity?->id);
        $selectedUniversityProgramId = old(
            'university_program_id',
            $mentor->university_program_id ?: $this->fallbackUniversityProgramId($mentor, $universityPrograms)
        );

        return view('settings::mentor.index', [
            'mentor' => $mentor,
            'user' => $user,
            'selectedUniversity' => $selectedUniversity,
            'selectedUniversityId' => $selectedUniversity?->id,
            'universityPrograms' => $universityPrograms,
            'selectedUniversityProgramId' => $selectedUniversityProgramId,
            'timezoneOptions' => TimezoneOptions::all(),
            'selectedTimezone' => old('timezone', $user->setting?->timezone ?? TimezoneOptions::fallback()),
            'hasSavedTimezone' => filled($user->setting?->timezone),
            'timezoneAutoSaveUrl' => route('settings.timezone.store'),
            'stripeReturn' => request()->boolean('stripe_return'),
            'zoomConfigured' => $this->zoom->isConfigured(),
            'zoomConnectionStatus' => $this->zoom->connectionStatusForUser($user),
            'zoomConnectUrl' => route('mentor.settings.zoom.connect'),
            'zoomDisconnectUrl' => route('mentor.settings.zoom.disconnect'),
            'zoomConnectedAccount' => $this->zoom->oauthTokenForUser($user)?->provider_user_id,
        ]);
    }

    public function universityPrograms(Request $request): JsonResponse
    {
        $universityId = $request->integer('university_id');

        if ($universityId <= 0) {
            return response()->json(['data' => []]);
        }

        $university = University::query()
            ->where('is_active', true)
            ->find($universityId);

        if (! $university) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->universityProgramsForUniversityId($university->id)
                ->map(fn (UniversityProgram $program): array => [
                    'id' => $program->id,
                    'program_name' => $program->program_name,
                    'program_type' => $program->program_type,
                ])
                ->values(),
        ]);
    }

    public function update(UpdateMentorSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('mentor.services', 'setting');
        $data = $request->validated();
        $avatar = $request->file('avatar');
        $mentor = null;

        DB::transaction(function () use ($user, $data, &$mentor): void {
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
            ])->save();

            $user->setting()->updateOrCreate([], [
                'theme' => $user->setting?->theme ?? 'light',
                'email_notifications' => $user->setting?->email_notifications ?? true,
                'sms_notifications' => $user->setting?->sms_notifications ?? false,
                'timezone' => $data['timezone'] ?? null,
            ]);

            $mentor = $user->mentor ?? new Mentor([
                'user_id' => $user->id,
            ]);
            $selectedUniversity = isset($data['university_id'])
                ? University::query()->find((int) $data['university_id'])
                : null;
            $selectedProgram = isset($data['university_program_id'])
                ? UniversityProgram::query()->find((int) $data['university_program_id'])
                : null;
            $gradSchoolDisplay = trim((string) ($data['grad_school_display'] ?? ''));
            if ($gradSchoolDisplay === '') {
                $gradSchoolDisplay = $selectedUniversity?->display_name ?: $selectedUniversity?->name ?: null;
            }

            $mentorData = [
                'university_id' => $selectedUniversity?->id,
                'mentor_type' => $data['mentor_type'],
                'title' => $data['title'] ?? null,
                'university_program_id' => $selectedProgram?->id,
                'program_type' => $selectedProgram?->program_type,
                'grad_school_display' => $gradSchoolDisplay,
                'bio' => $data['bio'] ?? null,
                'description' => $data['description'] ?? null,
                'edu_email' => $data['edu_email'] ?? null,
                'calendly_link' => $data['calendly_link'] ?? null,
            ];

            $mentor->fill($mentorData);
            $mentor->save();

        });

        if ($avatar && $mentor) {
            $this->avatars->replaceMentorAvatar($user->fresh(), $mentor->fresh(), $avatar);
        }

        return redirect()
            ->route('mentor.settings.index')
            ->with('success', 'Mentor profile updated successfully.');
    }

    public function connectZoom(Request $request): RedirectResponse
    {
        if (! $this->zoom->isConfigured()) {
            return redirect()
                ->route('mentor.settings.index')
                ->withErrors(['zoom' => 'Zoom OAuth is not configured right now.']);
        }

        $state = Str::random(40);
        $request->session()->put('mentor_zoom_oauth_state', $state);

        return redirect()->away($this->zoom->authorizationUrl($state));
    }

    public function handleZoomCallback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('mentor_zoom_oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()
                ->route('mentor.settings.index')
                ->withErrors(['zoom' => 'Zoom authorization could not be verified. Please try again.']);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()
                ->route('mentor.settings.index')
                ->withErrors(['zoom' => 'Zoom did not return an authorization code.']);
        }

        try {
            $this->zoom->connectUser(Auth::user(), $code);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('mentor.settings.index')
                ->withErrors(['zoom' => $exception->getMessage()]);
        }

        return redirect()
            ->route('mentor.settings.index')
            ->with('success', 'Zoom connected successfully.');
    }

    public function disconnectZoom(): RedirectResponse
    {
        $this->zoom->disconnectUser(Auth::user());

        return redirect()
            ->route('mentor.settings.index')
            ->with('success', 'Zoom disconnected successfully.');
    }

    private function universityProgramsForUniversityId(?int $universityId)
    {
        if (! $universityId) {
            return collect();
        }

        return UniversityProgram::query()
            ->where('university_id', $universityId)
            ->where('is_active', true)
            ->orderBy('program_name')
            ->get(['id', 'program_name', 'program_type']);
    }

    private function fallbackUniversityProgramId(Mentor $mentor, $universityPrograms): ?int
    {
        if (! $mentor->program_type) {
            return null;
        }

        return $universityPrograms
            ->firstWhere('program_type', $mentor->program_type)
            ?->id;
    }
}
