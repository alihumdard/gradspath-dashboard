<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Services\AdminAuditService;
use Modules\Payments\app\Http\Requests\StoreServiceRequest;
use Modules\Payments\app\Models\ServiceConfig;

class ServicesController extends Controller
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): View
    {
        $services = ServiceConfig::query()
            ->withCount('mentors')
            ->orderBy('sort_order')
            ->orderBy('service_name')
            ->get();

        return view('discovery::admin.admin', [
            'services' => $services,
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sessionTypes = collect($data['available_session_types'] ?? []);
        $notes = (string) ($data['notes'] ?? '');

        $service = ServiceConfig::create([
            'service_name' => $data['service_name'],
            'service_slug' => $this->generateUniqueSlug($data['service_name']),
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 60),
            'is_active' => $request->boolean('is_active'),
            'price_1on1' => $sessionTypes->contains('1on1') ? $data['price_1on1'] : null,
            'price_1on3_per_person' => $sessionTypes->contains('1on3') ? $data['price_1on3_per_person'] : null,
            'price_1on5_per_person' => $sessionTypes->contains('1on5') ? $data['price_1on5_per_person'] : null,
            'is_office_hours' => $request->boolean('is_office_hours'),
            'office_hours_subscription_price' => $request->boolean('is_office_hours') ? $data['office_hours_subscription_price'] : null,
            'credit_cost_1on1' => $sessionTypes->contains('1on1') ? (int) $data['credit_cost_1on1'] : 0,
            'credit_cost_1on3' => $sessionTypes->contains('1on3') ? (int) $data['credit_cost_1on3'] : 0,
            'credit_cost_1on5' => $sessionTypes->contains('1on5') ? (int) $data['credit_cost_1on5'] : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->audit->log(
            Auth::user(),
            'manual_service_create',
            'services_config',
            $service->id,
            null,
            $service->fresh()->toArray(),
            $notes
        );

        return $this->redirectToManualActions('services', "Service {$service->service_name} created successfully.");
    }

    public function update(Request $request, ?int $id = null): RedirectResponse
    {
        $serviceId = $id ?? (int) $request->integer('service_id');
        $service = ServiceConfig::query()->findOrFail($serviceId);
        $before = $service->toArray();

        $data = $request->validate([
            'service_id' => ['nullable', 'integer', 'exists:services_config,id'],
            'service_name' => ['sometimes', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:300'],
            'is_active' => ['sometimes', 'boolean'],
            'price_1on1' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on3_per_person' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on5_per_person' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_office_hours' => ['sometimes', 'boolean'],
            'office_hours_subscription_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'credit_cost_1on1' => ['sometimes', 'integer', 'min:0'],
            'credit_cost_1on3' => ['sometimes', 'integer', 'min:0'],
            'credit_cost_1on5' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['required', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $notes = (string) $data['notes'];
        unset($data['service_id'], $data['notes'], $data['manual_section']);

        $service->update($data);

        $this->audit->log(
            Auth::user(),
            'manual_service_update',
            'services_config',
            $service->id,
            $before,
            $service->fresh()->toArray(),
            $notes
        );

        return $this->redirectToManualActions('pricing', 'Service updated successfully.');
    }

    private function generateUniqueSlug(string $serviceName): string
    {
        $baseSlug = (string) Str::of($serviceName)->slug('_');
        $stem = $baseSlug !== '' ? $baseSlug : 'service';
        $slug = $stem;
        $counter = 2;

        while (ServiceConfig::query()->where('service_slug', $slug)->exists()) {
            $slug = "{$stem}_{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('manual_status', [
                'type' => 'success',
                'message' => $message,
            ]);
    }
}
