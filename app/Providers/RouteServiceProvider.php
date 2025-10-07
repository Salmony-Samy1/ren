<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $listen = [];
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Default API rate limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Admin dashboard rate limiter (tighter and user-scoped)
        RateLimiter::for('admin', function (Request $request) {
            $key = ($request->user()?->id ? 'u:'.$request->user()->id : 'ip:'.$request->ip());
            return Limit::perMinute(60)->by($key);
        });

        $this->routes(function () {
            // Public guest routes (no auth)
            Route::middleware('api')
                ->prefix('api/v1/public')
                ->group(base_path('routes/api/public.php'));

            // Customer context routes
            Route::middleware(['api', 'auth:api', 'user_type:customer'])
                ->prefix('api/v1/customer')
                ->group(base_path('routes/api/customer.php'));

            // Provider context routes
            Route::middleware(['api', 'auth:api', 'user_type:provider'])
                ->prefix('api/v1/provider')
                ->group(base_path('routes/api/provider.php'));

            // Admin routes (v1 canonical)
            // NOTE: Do NOT apply auth middleware globally here, so that public admin routes (e.g. /auth/login)
            // remain accessible. Route-level middleware is defined inside routes/api/admin.php for protected areas.
            Route::middleware(['api'])
                ->prefix('api/v1/admin')
                ->group(base_path('routes/api/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

