<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bind the property service contract to its implementation
        $this->app->bind(
            \App\Services\Contracts\IPropertyService::class,
            \App\Services\PropertyService::class
        );

        // Bind the event service contract to its implementation
        $this->app->bind(
            \App\Services\Contracts\IEventService::class,
            \App\Services\EventService::class
        );

        // Bind the restaurant service contract to its implementation
        $this->app->bind(
            \App\Services\Contracts\IRestaurantService::class,
            \App\Services\RestaurantService::class
        );

        // Bind the catering service contract to its implementation
        $this->app->bind(
            \App\Services\Contracts\ICateringService::class,
            \App\Services\CateringService::class
        );

        // Bind the catering item service contract to its implementation
        $this->app->bind(
            \App\Services\Contracts\ICateringItemService::class,
            \App\Services\CateringItemService::class
        );
        
        // Bind the service factory contract to its implementation
        $this->app->bind(
            \App\Services\ServiceCreation\Contracts\IServiceFactory::class,
            \App\Services\ServiceCreation\ServiceFactory::class
        );

        // Bind Service Update Strategies
        $this->app->bind(
            \App\Services\ServiceManagement\Strategies\PropertyUpdateStrategy::class,
            function ($app) {
                return new \App\Services\ServiceManagement\Strategies\PropertyUpdateStrategy(
                    $app->make(\App\Services\Contracts\IPropertyService::class)
                );
            }
        );

        $this->app->bind(
            \App\Services\ServiceManagement\Strategies\EventUpdateStrategy::class,
            function ($app) {
                return new \App\Services\ServiceManagement\Strategies\EventUpdateStrategy(
                    $app->make(\App\Services\Contracts\IEventService::class)
                );
            }
        );

        $this->app->bind(
            \App\Services\ServiceManagement\Strategies\RestaurantUpdateStrategy::class,
            function ($app) {
                return new \App\Services\ServiceManagement\Strategies\RestaurantUpdateStrategy(
                    $app->make(\App\Services\Contracts\IRestaurantService::class)
                );
            }
        );

        $this->app->bind(
            \App\Services\ServiceManagement\Strategies\CateringUpdateStrategy::class,
            function ($app) {
                return new \App\Services\ServiceManagement\Strategies\CateringUpdateStrategy(
                    $app->make(\App\Services\Contracts\ICateringService::class)
                );
            }
        );

        // Bind UnifiedServiceManager
        $this->app->bind(
            \App\Services\ServiceManagement\UnifiedServiceManager::class,
            function ($app) {
                return new \App\Services\ServiceManagement\UnifiedServiceManager(
                    $app->make(\App\Services\ServiceManagement\Strategies\PropertyUpdateStrategy::class),
                    $app->make(\App\Services\ServiceManagement\Strategies\EventUpdateStrategy::class),
                    $app->make(\App\Services\ServiceManagement\Strategies\RestaurantUpdateStrategy::class),
                    $app->make(\App\Services\ServiceManagement\Strategies\CateringUpdateStrategy::class),
                    $app->make(\App\Services\ServiceCreation\ServiceFactory::class)
                );
            }
        );

        // Bind AdminServiceManager
        $this->app->bind(
            \App\Services\ServiceManagement\AdminServiceManager::class,
            function ($app) {
                return new \App\Services\ServiceManagement\AdminServiceManager(
                    $app->make(\App\Services\ServiceManagement\UnifiedServiceManager::class)
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);
        \App\Models\Service::observe(\App\Observers\ServiceObserver::class);
        \App\Models\Category::observe(\App\Observers\CategoryObserver::class);
        \App\Models\MainService::observe(\App\Observers\MainServiceObserver::class);
    }
}
