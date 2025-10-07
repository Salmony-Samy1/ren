<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PhoneVerificationOptional
{
    public function handle(Request $request, Closure $next): Response
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        $response = $next($request);
        if ($user) {
            $response->headers->set('X-Phone-Verified', $user->phone_verified_at ? '1' : '0');
        }
        return $response;
    }
}

