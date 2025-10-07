<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsurePendingProfile
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $status = $payload->get('profile_status');
            if ($status !== 'pending_profile_completion') {
                return response()->json(['success' => false, 'message' => 'Invalid token for profile completion'], 403);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}

