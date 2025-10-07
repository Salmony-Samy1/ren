<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class UserTypeMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $roles = explode(',', $role);

        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();

        // Effective type can be switched via header X-User-Context, but only applies when a user exists
        $effective = $request->attributes->get('effective_user_type');
        if ($effective === null) {
            $effective = $user?->type;
        }

        if (!$user) {
            return response('Unauthorized.', 401);
        }
        if (!in_array((string)$effective, $roles, true)) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
