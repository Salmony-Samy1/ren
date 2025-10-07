<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        if (!$user) {
            abort(401);
        }
        // assumes users table has a 'type' column: 'customer' | 'provider' | 'admin'
        if ($user->type !== $role) {
            abort(403);
        }
        return $next($request);
    }
}

