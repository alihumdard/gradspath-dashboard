<?php

namespace Modules\Settings\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Http\Requests\UpdateMentorSettingsRequest;
use Modules\Settings\app\Models\Mentor;

class MentorSettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user()->loadMissing('mentor.services');
        $mentor = $user->mentor ?? new Mentor([
            'mentor_type' => 'graduate',
        ]);

        return view('settings::mentor.index', [
            'mentor' => $mentor,
            'user' => $user,
            'services' => ServiceConfig::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('service_name')
                ->get(),
            'selectedServiceIds' => $mentor->exists
                ? $mentor->services->pluck('id')->all()
                : [],
        ]);
    }

    public function update(UpdateMentorSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('mentor.services');
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'name' => $data['name'],
            ])->save();

            $mentor = $user->mentor ?? new Mentor([
                'user_id' => $user->id,
            ]);

            $mentor->fill([
                'mentor_type' => $data['mentor_type'],
                'title' => $data['title'] ?? null,
                'program_type' => $data['program_type'] ?? null,
                'grad_school_display' => $data['grad_school_display'] ?? null,
                'bio' => $data['bio'] ?? null,
                'description' => $data['description'] ?? null,
                'office_hours_schedule' => $data['office_hours_schedule'] ?? null,
                'edu_email' => $data['edu_email'] ?? null,
                'calendly_link' => $data['calendly_link'] ?? null,
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
}
