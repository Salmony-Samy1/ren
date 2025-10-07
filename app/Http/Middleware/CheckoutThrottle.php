<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CheckoutThrottle
{
    public function handle(Request $request, Closure $next)
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        $userId = optional($user)->id;
        $idempotencyKey = (string) $request->input('idempotency_key');
        $key = sprintf('checkout:%s:%s', $userId, $idempotencyKey);

        // Bypass throttle if this is an idempotent retry for an already-created order
        if ($userId && $idempotencyKey) {
            $exists = Order::where('user_id', $userId)->where('idempotency_key', $idempotencyKey)->exists();
            if ($exists) {
                return $next($request);
            }
        }

        $ttl = (int) (get_setting('checkout_confirm_throttle_ttl') ?? 5);
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['success' => false, 'message' => 'Too many attempts. Please wait and try again.'], 429);
        }
        RateLimiter::hit($key, $ttl);
        return $next($request);
    }
}

