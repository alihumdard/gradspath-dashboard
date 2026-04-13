<?php

namespace Modules\Institutions\app\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Institutions\app\Models\University;

class InstitutionService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return University::query()
            ->where('is_active', true)
            ->when(
                $filters['tier'] ?? null,
                fn ($q, $tier) => $q->whereHas(
                    'programs',
                    fn ($programs) => $programs
                        ->where('is_active', true)
                        ->where('tier', $tier)
                )
            )
            ->when($filters['q'] ?? null, fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function detail(int $id): University
    {
        return University::query()
            ->with(['programs' => fn($q) => $q->where('is_active', true), 'mentors.user:id,name,avatar_url'])
            ->findOrFail($id);
    }
}
