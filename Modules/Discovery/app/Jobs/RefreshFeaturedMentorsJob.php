<?php

namespace Modules\Discovery\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Discovery\app\Services\FeaturedMentorService;

class RefreshFeaturedMentorsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $limit = 6) {}

    public function handle(FeaturedMentorService $featuredMentors): void
    {
        $featuredMentors->refreshAutomatic($this->limit);
    }
}
