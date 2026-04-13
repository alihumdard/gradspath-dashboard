<?php

namespace Modules\Institutions\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Institutions\app\Http\Requests\StoreUniversityProgramRequest;
use Modules\Institutions\app\Http\Requests\UpdateUniversityProgramRequest;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;

class UniversityProgramsController extends Controller
{
    public function index(Request $request): View
    {
        $universities = University::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $programs = UniversityProgram::query()
            ->with('university:id,name')
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return view('discovery::admin.admin', [
            'programUniversities' => $universities,
            'universityPrograms' => $programs,
        ]);
    }

    public function store(StoreUniversityProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['manual_station']);
        $data['is_active'] = $request->boolean('is_active');

        $program = UniversityProgram::query()->create($data);

        return back()
            ->with('manual_station', 'program-create-station')
            ->with('success', "Program {$program->program_name} created successfully.");
    }

    public function update(UpdateUniversityProgramRequest $request, int $id): RedirectResponse
    {
        $program = UniversityProgram::query()->findOrFail($id);

        $data = $request->validated();
        unset($data['manual_station']);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $program->update($data);

        return back()
            ->with('manual_station', 'program-create-station')
            ->with('success', "Program {$program->program_name} updated successfully.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $program = UniversityProgram::query()->findOrFail($id);
        $programName = $program->program_name;
        $program->delete();

        return back()
            ->with('manual_station', 'program-create-station')
            ->with('success', "Program {$programName} deleted successfully.");
    }
}
