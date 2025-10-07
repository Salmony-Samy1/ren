<?php

namespace App\Http\Middleware;

use App\Services\RealtimePresence;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPresenceHeartbeat
{
    public function __construct(private readonly RealtimePresence $presence) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        try {
            $user = $request->user('api') ?: $request->user();
            if ($user) {
                // Record a lightweight heartbeat; TTL of 60s
                $this->presence->heartbeat($user, 60);
            }
        } catch (\Throwable $e) {
            // swallow errors; presence must not break API
        }
        return $response;
    }
}

