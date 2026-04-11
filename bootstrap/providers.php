<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\app\Providers\AuthServiceProvider;
use Modules\Discovery\app\Providers\DiscoveryServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    DiscoveryServiceProvider::class,
];
