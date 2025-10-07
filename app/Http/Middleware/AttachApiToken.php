<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Share token with views (Blade) and Livewire via session value
        if (session()->has('auth_token')) {
            view()->share('auth_token', session('auth_token'));
        }

        return $response;
    }
}

