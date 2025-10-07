<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContextSwitchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Try API guard (token-based) only if a Bearer token is present, then fallback to default user()
        $user = null;
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with(strtolower($authHeader), 'bearer ')) {
            try {
                $user = $request->user('api') ?? auth('api')->user();
            } catch (\Throwable $e) {
                // Silently ignore JWT errors here to avoid breaking non-API pages
            }
        }
        if (!$user) {
            $user = $request->user();
        }
        $ctx = $request->header('X-User-Context');
        if ($user && $ctx) {
            $ctx = strtolower($ctx);
            if (in_array($ctx, ['customer','provider','admin'])) {
                // downgrade only (can't elevate to admin unless already admin)
                if ($ctx === 'admin' && $user->type !== 'admin') {
                    // ignore
                } else {
                    // attach to request for downstream middleware/policies
                    $request->attributes->set('effective_user_type', $ctx);
                }
            }
        }
        return $next($request);
    }
}

