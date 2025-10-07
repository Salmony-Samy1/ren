<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (empty($user->phone) || empty($user->country_code) || !$user->phone_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Phone verification required',
                'verification_required' => true,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
            ], 403);
        }
        return $next($request);
    }
}

