<?php

namespace Modules\Settings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Http\Requests\UpdateMentorSettingsRequest;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Support\TimezoneOptions;

class MentorSettingsController extends Controller
{
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
            'services' => ServiceConfig::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('service_name')
                ->get(),
            'selectedServiceIds' => $mentor->exists
                ? $mentor->services->pluck('id')->all()
                : [],
            'timezoneOptions' => TimezoneOptions::all(),
            'selectedTimezone' => old('timezone', $user->setting?->timezone ?? TimezoneOptions::fallback()),
            'hasSavedTimezone' => filled($user->setting?->timezone),
            'timezoneAutoSaveUrl' => route('settings.timezone.store'),
            'stripeReturn' => request()->boolean('stripe_return'),
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

        DB::transaction(function () use ($user, $data): void {
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

            $mentor->fill([
                'university_id' => $selectedUniversity?->id,
                'mentor_type' => $data['mentor_type'],
                'title' => $data['title'] ?? null,
                'university_program_id' => $selectedProgram?->id,
                'program_type' => $selectedProgram?->program_type,
                'grad_school_display' => $gradSchoolDisplay,
                'bio' => $data['bio'] ?? null,
                'description' => $data['description'] ?? null,
                'office_hours_schedule' => $data['office_hours_schedule'] ?? null,
                'edu_email' => $data['edu_email'] ?? null,
                'calendly_link' => $data['calendly_link'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ]);
            $mentor->save();

            $serviceIds = collect($data['service_config_ids'] ?? [])
                ->map(fn (mixed $id) => (int) $id)
                ->values();

            $mentor->services()->sync(
                $serviceIds->mapWithKeys(fn (int $id, int $index) => [
                    $id => ['sort_order' => $index],
                ])->all()
            );
        });

        return redirect()
            ->route('mentor.settings.index')
            ->with('success', 'Mentor profile updated successfully.');
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
