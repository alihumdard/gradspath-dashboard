<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Settings\app\Models\Mentor;

class MentorDiscoveryService
{
    public function search(array $filters): LengthAwarePaginator
    {
        return Mentor::query()
            ->with(['user:id,name,email,avatar_url', 'university:id,name,display_name', 'rating'])
            ->where('status', 'active')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('bio', 'like', "%{$q}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['mentor_type'] ?? null, fn($q, $type) => $q->where('mentor_type', $type))
            ->when($filters['program_type'] ?? null, fn($q, $type) => $q->where('program_type', $type))
            ->when($filters['university_id'] ?? null, fn($q, $id) => $q->where('university_id', $id))
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function featured(int $limit = 6)
    {
        return Mentor::query()
            ->with(['user:id,name,avatar_url', 'university:id,name,display_name', 'rating'])
            ->where('status', 'active')
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }
}
