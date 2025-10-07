<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Secure the broadcasting auth endpoint for private channels via API (JWT)
        Broadcast::routes([
            'middleware' => ['auth:api', 'throttle:60,1'],
        ]);

        require base_path('routes/channels.php');
    }
}

