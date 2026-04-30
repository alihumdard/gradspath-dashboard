<?php

namespace Modules\Institutions\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Institutions\app\Http\Requests\StoreUniversityProgramRequest;
use Modules\Institutions\app\Http\Requests\UpdateUniversityProgramRequest;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;

class UniversityProgramsController extends Controller
{
    public function __construct(private readonly AdminAuditService $audit) {}

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

    public function searchUniversities(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $selectedId = $request->integer('selected_id');
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 50);

        $universities = University::query()
            ->where('is_active', true)
            ->when($selectedId > 0, fn ($builder) => $builder->where('id', $selectedId))
            ->when($selectedId === 0 && $query !== '', function ($builder) use ($query): void {
                $builder->where(function ($inner) use ($query): void {
                    $inner
                        ->where('name', 'like', '%'.$query.'%')
                        ->orWhere('display_name', 'like', '%'.$query.'%');
                });
            })
            ->orderByRaw('COALESCE(display_name, name)')
            ->paginate($perPage, ['id', 'name', 'display_name', 'country', 'state_province']);

        return response()->json([
            'data' => $universities->getCollection()
                ->map(fn (University $university): array => [
                    'id' => $university->id,
                    'label' => $university->display_name ?: $university->name,
                    'name' => $university->name,
                    'country' => $university->country,
                    'state_province' => $university->state_province,
                ])
                ->values(),
            'next_page' => $universities->hasMorePages() ? $universities->currentPage() + 1 : null,
        ]);
    }

    public function store(StoreUniversityProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $notes = $data['notes'] ?? null;
        unset($data['manual_station'], $data['manual_section'], $data['notes']);
        $data['is_active'] = $request->boolean('is_active');

        $program = UniversityProgram::query()->create($data);

        $this->audit->log(
            Auth::user(),
            'manual_program_create',
            'university_programs',
            $program->id,
            null,
            $program->fresh()->toArray(),
            $notes
        );

        return $this->redirectToManualActions('programs', "Program {$program->program_name} created successfully.");
    }

    public function update(UpdateUniversityProgramRequest $request, int $id): RedirectResponse
    {
        $program = UniversityProgram::query()->findOrFail($id);
        $before = $program->toArray();

        $data = $request->validated();
        $notes = $data['notes'] ?? null;
        unset($data['manual_station'], $data['manual_section'], $data['notes']);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $program->update($data);

        $this->audit->log(
            Auth::user(),
            'manual_program_update',
            'university_programs',
            $program->id,
            $before,
            $program->fresh()->toArray(),
            $notes
        );

        return $this->redirectToManualActions('programs', "Program {$program->program_name} updated successfully.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $program = UniversityProgram::query()->findOrFail($id);
        $programName = $program->program_name;
        $program->delete();

        return $this->redirectToManualActions('programs', "Program {$programName} deleted successfully.");
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('success', $message);
    }
}
