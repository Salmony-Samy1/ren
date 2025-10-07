<?php

return [
    App\Providers\AppServiceProvider::class,
    \App\Providers\RepositoryServiceProvider::class,
    \App\Providers\RouteServiceProvider::class,
    \App\Providers\AuthServiceProvider::class,
    \App\Providers\EventServiceProvider::class,
    \App\Providers\AdminOrganizationServiceProvider::class,

    // \QCod\Gamify\GamifyServiceProvider::class,
    willvincent\Rateable\RateableServiceProvider::class,
    App\Providers\Bindings\PaymentBindingServiceProvider::class,
    App\Providers\BookingPricingServiceProvider::class,
    App\Providers\BookingFactoryServiceProvider::class,
];
