<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
        $notes = $data['notes'] ?? null;

        $service = ServiceConfig::create([
            'service_name' => $data['service_name'],
            'service_slug' => $this->generateUniqueSlug($data['service_name']),
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 60),
            'is_active' => $request->boolean('is_active'),
            'price_1on1' => $sessionTypes->contains('1on1') ? $data['price_1on1'] : null,
            'platform_fee_1on1' => $sessionTypes->contains('1on1') ? $data['platform_fee_1on1'] : null,
            'mentor_payout_1on1' => $sessionTypes->contains('1on1') ? $data['mentor_payout_1on1'] : null,
            'price_1on3_per_person' => $sessionTypes->contains('1on3') ? $this->perPerson($data['price_1on3_total'], 3) : null,
            'price_1on3_total' => $sessionTypes->contains('1on3') ? $data['price_1on3_total'] : null,
            'platform_fee_1on3' => $sessionTypes->contains('1on3') ? $data['platform_fee_1on3'] : null,
            'mentor_payout_1on3' => $sessionTypes->contains('1on3') ? $data['mentor_payout_1on3'] : null,
            'price_1on5_per_person' => $sessionTypes->contains('1on5') ? $this->perPerson($data['price_1on5_total'], 5) : null,
            'price_1on5_total' => $sessionTypes->contains('1on5') ? $data['price_1on5_total'] : null,
            'platform_fee_1on5' => $sessionTypes->contains('1on5') ? $data['platform_fee_1on5'] : null,
            'mentor_payout_1on5' => $sessionTypes->contains('1on5') ? $data['mentor_payout_1on5'] : null,
            'is_office_hours' => $request->boolean('is_office_hours'),
            'office_hours_subscription_price' => $request->boolean('is_office_hours') ? $data['office_hours_subscription_price'] : null,
            'office_hours_mentor_payout_per_attendee' => $request->boolean('is_office_hours') ? $data['office_hours_mentor_payout_per_attendee'] : null,
            'credit_cost_1on1' => 0,
            'credit_cost_1on3' => 0,
            'credit_cost_1on5' => 0,
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
            'platform_fee_1on1' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mentor_payout_1on1' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on3_per_person' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on3_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'platform_fee_1on3' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mentor_payout_1on3' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on5_per_person' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_1on5_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'platform_fee_1on5' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mentor_payout_1on5' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_office_hours' => ['sometimes', 'boolean'],
            'office_hours_subscription_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'office_hours_mentor_payout_per_attendee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ]);

        $notes = $data['notes'] ?? null;
        unset($data['service_id'], $data['notes'], $data['manual_section']);

        $this->validateSplitRequest($request, $service);

        if (array_key_exists('price_1on3_total', $data)) {
            $data['price_1on3_per_person'] = $data['price_1on3_total'] !== null
                ? $this->perPerson($data['price_1on3_total'], 3)
                : null;
        } elseif (array_key_exists('price_1on3_per_person', $data)) {
            $data['price_1on3_total'] = $data['price_1on3_per_person'] !== null
                ? $this->groupTotal($data['price_1on3_per_person'], 3)
                : null;
        }

        if (array_key_exists('price_1on5_total', $data)) {
            $data['price_1on5_per_person'] = $data['price_1on5_total'] !== null
                ? $this->perPerson($data['price_1on5_total'], 5)
                : null;
        } elseif (array_key_exists('price_1on5_per_person', $data)) {
            $data['price_1on5_total'] = $data['price_1on5_per_person'] !== null
                ? $this->groupTotal($data['price_1on5_per_person'], 5)
                : null;
        }

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

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $service = ServiceConfig::query()->findOrFail($id);
        $before = $service->toArray();
        $serviceName = $service->service_name;

        \DB::transaction(function () use ($service) {
            $service->bookings()->delete();
            $service->delete();
        });

        $this->audit->log(
            Auth::user(),
            'manual_service_delete',
            'services_config',
            $id,
            $before,
            null,
            "Deleted service {$serviceName} and related bookings."
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Service {$serviceName} deleted successfully.",
            ]);
        }

        return redirect()
            ->route('admin.services')
            ->with('success', "Service {$serviceName} deleted successfully.");
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

    private function groupTotal(mixed $perPerson, int $groupSize): float
    {
        return round((float) $perPerson * $groupSize, 2);
    }

    private function perPerson(mixed $total, int $groupSize): float
    {
        return round((float) $total / $groupSize, 2);
    }

    private function validateSplitRequest(Request $request, ServiceConfig $service): void
    {
        $rules = [
            '1on1' => ['price_1on1', 'platform_fee_1on1', 'mentor_payout_1on1'],
            '1on3' => ['price_1on3_total', 'platform_fee_1on3', 'mentor_payout_1on3'],
            '1on5' => ['price_1on5_total', 'platform_fee_1on5', 'mentor_payout_1on5'],
        ];

        foreach ($rules as $fields) {
            [$priceField, $platformField, $mentorField] = $fields;

            if (! $request->hasAny($fields)) {
                continue;
            }

            $submittedValues = collect($fields)
                ->filter(fn (string $field): bool => $request->has($field))
                ->map(fn (string $field): mixed => $request->input($field));

            if ($submittedValues->isNotEmpty() && $submittedValues->every(fn (mixed $value): bool => $value === null || $value === '')) {
                continue;
            }

            $price = $request->has($priceField) ? $request->input($priceField) : $service->{$priceField};
            $platform = $request->has($platformField) ? $request->input($platformField) : $service->{$platformField};
            $mentor = $request->has($mentorField) ? $request->input($mentorField) : $service->{$mentorField};

            if ($price === null || $price === '' || $platform === null || $platform === '' || $mentor === null || $mentor === '') {
                throw ValidationException::withMessages([
                    $platformField => 'Student price, admin split, and mentor split are required together.',
                    $mentorField => 'Student price, admin split, and mentor split are required together.',
                ]);
            }

            $priceCents = (int) round((float) $price * 100);
            $splitCents = (int) round(((float) $platform + (float) $mentor) * 100);

            if ($priceCents !== $splitCents) {
                throw ValidationException::withMessages([
                    $platformField => 'Admin and mentor split amounts must add up to the student price.',
                    $mentorField => 'Admin and mentor split amounts must add up to the student price.',
                ]);
            }
        }
    }

    private function redirectToManualActions(string $section, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.manual-actions')
            ->with('manual_section', $section)
            ->with('success', $message);
    }
}
