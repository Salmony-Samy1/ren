<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForceApiGuard
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure the default guard for this request lifecycle is 'api'
        Auth::shouldUse('api');
        return $next($request);
    }
}

