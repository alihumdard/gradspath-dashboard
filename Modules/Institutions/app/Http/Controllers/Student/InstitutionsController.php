<?php

namespace Modules\Institutions\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Institutions\app\Http\Requests\FilterUniversitiesRequest;
use Modules\Institutions\app\Services\InstitutionService;

class InstitutionsController extends Controller
{
    public function __construct(private readonly InstitutionService $institutions)
    {
    }

    public function index(FilterUniversitiesRequest $request): View
    {
        $filters = $request->validated();
        $result = $this->institutions->list($filters);

        return view('institutions::student.index', [
            'institutions' => $result,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $institution = $this->institutions->detail($id);

        return view('institutions::student.show', [
            'institution' => $institution->toArray(),
        ]);
    }

    public function programs(int $id): RedirectResponse
    {
        return redirect()->route('student.institutions.show', $id);
    }

    public function mentors(int $id): RedirectResponse
    {
        return redirect()->route('student.institutions.show', $id);
    }
}
