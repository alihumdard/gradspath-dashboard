<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Modules\Institutions\app\Models\University;

class TopInstitutionService
{
    public function forDashboard(int $limit = 6): Collection
    {
        return University::query()
            ->where('universities.is_active', true)
            ->select([
                'universities.id',
                'universities.name',
                'universities.display_name',
                'universities.logo_url',
                DB::raw('COUNT(bookings.id) as bookings_count'),
            ])
            ->leftJoin('mentors', 'mentors.university_id', '=', 'universities.id')
            ->leftJoin('bookings', function (JoinClause $join): void {
                $join->on('bookings.mentor_id', '=', 'mentors.id')
                    ->whereIn('bookings.status', ['confirmed', 'completed']);
            })
            ->groupBy([
                'universities.id',
                'universities.name',
                'universities.display_name',
                'universities.logo_url',
            ])
            ->orderByDesc('bookings_count')
            ->orderBy('universities.name')
            ->limit($limit)
            ->get();
    }
}
