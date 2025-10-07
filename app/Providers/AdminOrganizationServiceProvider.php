<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AdminOrganizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Admin Controllers
        $this->app->bind(
            \App\Http\Controllers\Admin\Users\UsersController::class,
            function ($app) {
                return new \App\Http\Controllers\Admin\Users\UsersController(
                    $app->make(\App\Repositories\UserRepo\IUserRepo::class)
                );
            }
        );

        $this->app->bind(
            \App\Http\Controllers\Admin\Providers\ProvidersController::class,
            function ($app) {
                return new \App\Http\Controllers\Admin\Providers\ProvidersController(
                    $app->make(\App\Repositories\UserRepo\IUserRepo::class)
                );
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register route model bindings
        Route::model('user', \App\Models\User::class);
        Route::model('provider', \App\Models\User::class);
        Route::model('review', \App\Models\Review::class);
        Route::model('alert', \App\Models\Alert::class);

        // Add route constraints
        Route::pattern('user', '[0-9]+');
        Route::pattern('provider', '[0-9]+');
        Route::pattern('review', '[0-9]+');
        Route::pattern('alert', '[0-9]+');
    }
}


