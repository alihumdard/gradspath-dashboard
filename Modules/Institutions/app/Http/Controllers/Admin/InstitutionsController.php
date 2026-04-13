<?php

namespace Modules\Institutions\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\Institutions\app\Models\University;

class InstitutionsController extends Controller
{
    public function index(Request $request): View
    {
        $universities = University::query()->with('programs')->paginate((int) $request->integer('per_page', 20));

        return view('discovery::admin.admin', [
            'universities' => $universities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUniversityData($request);

        $university = University::create($data);

        return back()
            ->with('manual_station', 'institution-station')
            ->with('success', "Institution {$university->name} created successfully.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $university = University::query()->findOrFail($id);

        $data = $this->validateUniversityData($request, true);

        $university->update($data);

        return back()
            ->with('manual_station', 'institution-station')
            ->with('success', 'Institution updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $university = University::query()->findOrFail($id);
        $university->delete();

        return back()->with('success', 'Institution deleted successfully.');
    }

    private function validateUniversityData(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'display_name' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'country' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:50'],
            'alpha_two_code' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'size:2'],
            'city' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'domains' => [$isUpdate ? 'sometimes' : 'nullable', 'string'],
            'web_pages' => [$isUpdate ? 'sometimes' : 'nullable', 'string'],
            'state_province' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'logo_url' => [$isUpdate ? 'sometimes' : 'nullable', 'url'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'manual_station' => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        if (!$isUpdate) {
            $data['country'] = isset($data['country']) && $data['country'] !== ''
                ? $data['country']
                : 'US';
        }

        if (array_key_exists('alpha_two_code', $data) && $data['alpha_two_code'] !== null) {
            $data['alpha_two_code'] = strtoupper(trim((string) $data['alpha_two_code']));
        }

        if (array_key_exists('domains', $data)) {
            $data['domains'] = $this->normalizeLineList($data['domains']);
        }

        if (array_key_exists('web_pages', $data)) {
            $data['web_pages'] = $this->normalizeLineList($data['web_pages']);
        }

        if (array_key_exists('web_pages', $data)) {
            Validator::make(
                ['web_pages' => $data['web_pages']],
                ['web_pages.*' => ['nullable', 'url']]
            )->validate();
        }

        unset($data['manual_station']);

        return $data;
    }

    private function normalizeLineList(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return $items === [] ? null : $items;
    }
}
