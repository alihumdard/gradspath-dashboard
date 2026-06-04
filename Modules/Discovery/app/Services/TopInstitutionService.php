<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Modules\Institutions\app\Models\FeaturedInstitution;
use Modules\Institutions\app\Models\FeaturedInstitutionSetting;
use Modules\Institutions\app\Models\University;

class TopInstitutionService
{
    public function forDashboard(int $limit = 5): Collection
    {
        return $this->manualWithAutomaticFill($limit);
    }

    public function setting(): FeaturedInstitutionSetting
    {
        return FeaturedInstitutionSetting::query()->first()
            ?? FeaturedInstitutionSetting::query()->create([
                'mode' => FeaturedInstitutionSetting::MODE_AUTOMATIC,
            ]);
    }

    public function automaticPreview(int $limit = 5): Collection
    {
        $stored = $this->storedInstitutions(FeaturedInstitution::SOURCE_AUTOMATIC, $limit);

        return $stored->isNotEmpty() ? $stored : $this->liveAutomaticRanking($limit);
    }

    public function manualSelections(): Collection
    {
        return $this->storedInstitutions(FeaturedInstitution::SOURCE_MANUAL, 100);
    }

    public function refreshAutomatic(int $limit = 5): Collection
    {
        $institutions = $this->liveAutomaticRanking($limit);

        DB::transaction(function () use ($institutions): void {
            FeaturedInstitution::query()
                ->where('source', FeaturedInstitution::SOURCE_AUTOMATIC)
                ->delete();

            $institutions->values()->each(function (University $institution, int $index): void {
                FeaturedInstitution::query()->create([
                    'university_id' => $institution->id,
                    'sort_order' => $index + 1,
                    'meetings_count' => (int) $institution->bookings_count,
                    'source' => FeaturedInstitution::SOURCE_AUTOMATIC,
                ]);
            });

            $this->setting()->forceFill([
                'last_recalculated_at' => now(),
            ])->save();
        });

        return $institutions;
    }

    /**
     * @param  array<int, array{university_id:int|string|null, sort_order:int|string|null}>  $rows
     */
    public function saveManual(array $rows): FeaturedInstitutionSetting
    {
        return DB::transaction(function () use ($rows): FeaturedInstitutionSetting {
            $setting = $this->setting();
            $setting->forceFill(['mode' => FeaturedInstitutionSetting::MODE_AUTOMATIC])->save();

            FeaturedInstitution::query()
                ->where('source', FeaturedInstitution::SOURCE_MANUAL)
                ->delete();

            $universityIds = collect($rows)
                ->map(fn (array $row): int => (int) ($row['university_id'] ?? 0))
                ->filter()
                ->unique()
                ->values();

            $activeIds = University::query()
                ->whereIn('id', $universityIds)
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            collect($rows)
                ->filter(fn (array $row): bool => in_array((int) ($row['university_id'] ?? 0), $activeIds, true))
                ->unique(fn (array $row): int => (int) $row['university_id'])
                ->sortBy(fn (array $row): int => max(1, (int) ($row['sort_order'] ?? 9999)))
                ->values()
                ->each(function (array $row, int $index): void {
                    FeaturedInstitution::query()->create([
                        'university_id' => (int) $row['university_id'],
                        'sort_order' => max(1, (int) ($row['sort_order'] ?? ($index + 1))),
                        'meetings_count' => 0,
                        'source' => FeaturedInstitution::SOURCE_MANUAL,
                    ]);
                });

            return $setting->fresh();
        });
    }

    private function storedInstitutions(string $source, int $limit): Collection
    {
        return University::query()
            ->where('universities.is_active', true)
            ->join('featured_institutions', function (JoinClause $join) use ($source): void {
                $join->on('featured_institutions.university_id', '=', 'universities.id')
                    ->where('featured_institutions.source', $source);
            })
            ->select([
                'universities.id',
                'universities.name',
                'universities.display_name',
                'universities.logo_url',
                DB::raw('featured_institutions.meetings_count as bookings_count'),
            ])
            ->orderBy('featured_institutions.sort_order')
            ->orderBy('universities.name')
            ->limit($limit)
            ->get();
    }

    private function manualWithAutomaticFill(int $limit): Collection
    {
        $manual = $this->storedInstitutions(FeaturedInstitution::SOURCE_MANUAL, $limit);

        if ($manual->count() >= $limit) {
            return $manual;
        }

        $manualIds = $manual->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $remaining = $limit - $manual->count();

        $automatic = $this->storedInstitutions(FeaturedInstitution::SOURCE_AUTOMATIC, $limit)
            ->when(
                $manualIds !== [],
                fn (Collection $institutions): Collection => $institutions
                    ->reject(fn (University $institution): bool => in_array((int) $institution->id, $manualIds, true))
                    ->values()
            )
            ->take($remaining);

        if ($automatic->count() < $remaining) {
            $automaticIds = $automatic->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $excludedIds = array_values(array_unique([...$manualIds, ...$automaticIds]));

            $automatic = $automatic
                ->concat($this->liveAutomaticRanking($limit + count($excludedIds))
                    ->reject(fn (University $institution): bool => in_array((int) $institution->id, $excludedIds, true))
                    ->take($remaining - $automatic->count()))
                ->values();
        }

        return new Collection($manual->concat($automatic)->take($limit)->values()->all());
    }

    private function liveAutomaticRanking(int $limit = 5): Collection
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
