<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LangMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Priority: route param -> query -> header -> cookie -> session -> default
        $lang = $request->route('lang')
            ?? $request->query('lang')
            ?? $request->header('Accept-Language')
            ?? $request->header('x-language')
            ?? $request->cookie('lang')
            ?? session('lang', config('app.locale', 'ar'));

        $lang = strtolower($lang ?? 'ar');
        $lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';

        // Persist for subsequent requests (web)
        session(['lang' => $lang]);
        cookie()->queue(cookie('lang', $lang, 60 * 24 * 30)); // 30 days

        app()->setLocale($lang);
        App::setLocale($lang);
        return $next($request);
    }
}
