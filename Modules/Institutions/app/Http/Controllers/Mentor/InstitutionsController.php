<?php

namespace Modules\Institutions\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Institutions\app\Http\Requests\FilterUniversitiesRequest;
use Modules\Institutions\app\Services\InstitutionService;

class InstitutionsController extends Controller
{
    public function __construct(private readonly InstitutionService $institutions) {}

    public function index(FilterUniversitiesRequest $request): View
    {
        $filters = $request->validated();
        $result = $this->institutions->list($filters);

        return view('institutions::mentor.index', [
            'institutions' => $result,
            'institutionsData' => $this->institutions->browseData(),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $institution = $this->institutions->detail($id);

        return view('institutions::mentor.show', [
            'institution' => $institution->toArray(),
        ]);
    }
}
