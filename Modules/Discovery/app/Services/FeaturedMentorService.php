<?php

namespace Modules\Discovery\app\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Settings\app\Models\Mentor;

class FeaturedMentorService
{
    public function idsForDisplay(int $limit = 6): Collection
    {
        $manualIds = $this->manualFeaturedIds($limit);

        if ($manualIds->count() >= $limit) {
            return $manualIds;
        }

        $fillIds = $this->topRatedMentorQuery()
            ->whereNotIn('mentors.id', $manualIds->all())
            ->limit($limit - $manualIds->count())
            ->pluck('mentors.id');

        return $manualIds->merge($fillIds)->values();
    }

    public function refreshAutomatic(int $limit = 6): Collection
    {
        $ids = $this->topRatedMentorQuery()
            ->limit($limit)
            ->pluck('mentors.id');

        DB::transaction(function () use ($ids): void {
            Mentor::query()->update([
                'is_featured' => false,
                'featured_sort_order' => null,
            ]);

            $ids->each(function ($id, int $index): void {
                Mentor::query()
                    ->whereKey((int) $id)
                    ->update([
                        'is_featured' => true,
                        'featured_sort_order' => $index + 1,
                    ]);
            });
        });

        return $ids->values();
    }

    private function manualFeaturedIds(int $limit): Collection
    {
        return $this->baseMentorQuery()
            ->where('mentors.is_featured', true)
            ->orderByRaw('COALESCE(mentors.featured_sort_order, 9999)')
            ->tap(fn (Builder $query) => $this->orderByRating($query))
            ->limit($limit)
            ->pluck('mentors.id');
    }

    private function topRatedMentorQuery(): Builder
    {
        return $this->baseMentorQuery()
            ->tap(fn (Builder $query) => $this->orderByRating($query));
    }

    private function baseMentorQuery(): Builder
    {
        return Mentor::query()
            ->withAggregate('rating as rating_avg_stars', 'avg_stars')
            ->withAggregate('rating as rating_total_reviews', 'total_reviews')
            ->withAggregate('rating as rating_total_sessions', 'total_sessions')
            ->where('mentors.status', 'active')
            ->whereHas('user');
    }

    private function orderByRating(Builder $query): void
    {
        $query
            ->orderByDesc('rating_avg_stars')
            ->orderByDesc('rating_total_reviews')
            ->orderByDesc('rating_total_sessions')
            ->orderByDesc('mentors.id');
    }
}
