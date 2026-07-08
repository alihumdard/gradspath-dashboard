<?php

namespace Modules\Institutions\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Discovery\app\Services\TopInstitutionService;
use Modules\Institutions\app\Models\University;

class InstitutionsController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $audit,
        private readonly TopInstitutionService $topInstitutions
    ) {}

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

        $this->audit->log(
            Auth::user(),
            'manual_institution_create',
            'universities',
            $university->id,
            null,
            $university->fresh()->toArray(),
            $request->input('notes')
        );

        return $this->redirectToManualActions('institutions', "Institution {$university->name} created successfully.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $university = University::query()->findOrFail($id);
        $before = $university->toArray();

        $data = $this->validateUniversityData($request, true);

        $university->update($data);

        $this->audit->log(
            Auth::user(),
            'manual_institution_update',
            'universities',
            $university->id,
            $before,
            $university->fresh()->toArray(),
            $request->input('notes')
        );

        return $this->redirectToManualActions('institutions', 'Institution updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $university = University::query()->findOrFail($id);
        $university->delete();

        return $this->redirectToManualActions('institutions', 'Institution deleted successfully.');
    }

    public function updateFeatured(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'institutions' => ['nullable', 'array', 'max:5'],
            'institutions.*.university_id' => [
                'nullable',
                'integer',
                Rule::exists('universities', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'institutions.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:5'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $before = [
            'institution_ids' => $this->topInstitutions->manualSelections()->pluck('id')->values()->all(),
        ];

        $this->topInstitutions->saveManual($data['institutions'] ?? []);

        $after = [
            'institution_ids' => $this->topInstitutions->manualSelections()->pluck('id')->values()->all(),
        ];

        $this->audit->log(
            Auth::user(),
            'update_featured_institutions',
            'featured_institutions',
            null,
            $before,
            $after
        );

        return $this->redirectToManualActions('institutions', 'Featured institutions updated successfully.');
    }

    private function validateUniversityData(Request $request, bool $isUpdate = false): array
    {
        // The University model casts domains/web_pages as array; if the form repopulates
        // from an existing record those fields arrive as arrays — normalize to string first.
        foreach (['domains', 'web_pages'] as $field) {
            if (is_array($request->input($field))) {
                $request->merge([$field => implode("\n", $request->input($field))]);
            }
        }

        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'display_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'country' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'alpha_two_code' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'size:2'],
            'city' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'domains' => [$isUpdate ? 'sometimes' : 'nullable', 'string'],
            'web_pages' => [$isUpdate ? 'sometimes' : 'nullable', 'string'],
            'state_province' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'logo_file' => [$isUpdate ? 'sometimes' : 'required', 'nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'max:2048'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_station' => ['nullable', 'string'],
            'manual_section' => ['nullable', 'string'],
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

        if ($request->hasFile('logo_file')) {
            $data['logo_url'] = $this->storeLogoUpload($request);
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

        unset($data['manual_station'], $data['manual_section'], $data['notes'], $data['logo_file']);

        return $data;
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('success', $message);
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

    private function normalizeNullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function storeLogoUpload(Request $request): string
    {
        $file = $request->file('logo_file');
        $directory = public_path('university_logo');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $baseName = Str::slug((string) $request->input('name', 'institution-logo')) ?: 'institution-logo';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = $baseName.'-'.Str::lower(Str::random(8)).'.'.$extension;

        $file->move($directory, $filename);

        return 'university_logo/'.$filename;
    }
}
