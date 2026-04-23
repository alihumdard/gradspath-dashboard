<?php

return [
    App\Providers\AppServiceProvider::class,
    // App\Providers\HorizonServiceProvider::class,
    Modules\Auth\app\Providers\AuthServiceProvider::class,
    Modules\Discovery\app\Providers\DiscoveryServiceProvider::class,
];
