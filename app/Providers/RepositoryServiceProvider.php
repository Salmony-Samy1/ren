<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
            $this->app->bind(\App\Repositories\UserRepo\IUserRepo::class, \App\Repositories\UserRepo\UserRepo::class);
            $this->app->bind(\App\Repositories\CityRepo\ICityRepo::class, \App\Repositories\CityRepo\CityRepo::class);
                $this->app->bind(\App\Repositories\CompanyProfileRepo\ICompanyProfileRepo::class, \App\Repositories\CompanyProfileRepo\CompanyProfileRepo::class);
                $this->app->bind(\App\Repositories\CustomerProfileRepo\ICustomerProfileRepo::class, \App\Repositories\CustomerProfileRepo\CustomerProfileRepo::class);
                $this->app->bind(\App\Repositories\CustomerHobbyRepo\ICustomerHobbyRepo::class, \App\Repositories\CustomerHobbyRepo\CustomerHobbyRepo::class);
                $this->app->bind(\App\Repositories\NeigbourhoodRepo\INeigbourhoodRepo::class, \App\Repositories\NeigbourhoodRepo\NeigbourhoodRepo::class);
                $this->app->bind(\App\Repositories\RegionRepo\IRegionRepo::class, \App\Repositories\RegionRepo\RegionRepo::class);
                $this->app->bind(\App\Repositories\RoleRepo\IRoleRepo::class, \App\Repositories\RoleRepo\RoleRepo::class);
                $this->app->bind(\App\Repositories\PermissionRepo\IPermissionRepo::class, \App\Repositories\PermissionRepo\PermissionRepo::class);
                $this->app->bind(\App\Repositories\CategoryRepo\ICategoryRepo::class, \App\Repositories\CategoryRepo\CategoryRepo::class);
                $this->app->bind(\App\Repositories\FormRepo\IFormRepo::class, \App\Repositories\FormRepo\FormRepo::class);
                $this->app->bind(\App\Repositories\ServiceRepo\IServiceRepo::class, \App\Repositories\ServiceRepo\ServiceRepo::class);
                $this->app->bind(\App\Repositories\ActivityRepo\IActivityRepo::class, \App\Repositories\ActivityRepo\ActivityRepo::class);
                $this->app->bind(\App\Repositories\ActivityImageRepo\IActivityImageRepo::class, \App\Repositories\ActivityImageRepo\ActivityImageRepo::class);
                $this->app->bind(\App\Repositories\FollowRepo\IFollowRepo::class, \App\Repositories\FollowRepo\FollowRepo::class);
                $this->app->bind(\App\Repositories\UserNotificationRepo\IUserNotificationRepo::class, \App\Repositories\UserNotificationRepo\UserNotificationRepo::class);
        // bindings
    }

    public function boot(): void {}
}
