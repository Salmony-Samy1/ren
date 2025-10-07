<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Booking\Pricing\FeeCalculator;
use App\Domain\Booking\Pricing\Strategies\{EventFeeStrategy, CateringFeeStrategy, RestaurantFeeStrategy, PropertyFeeStrategy};

class BookingPricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register strategies as a tagged iterable
        $this->app->bind(EventFeeStrategy::class);
        $this->app->bind(CateringFeeStrategy::class);
        $this->app->bind(RestaurantFeeStrategy::class);
        $this->app->bind(PropertyFeeStrategy::class);

        $this->app->tag([
            EventFeeStrategy::class,
            CateringFeeStrategy::class,
            RestaurantFeeStrategy::class,
            PropertyFeeStrategy::class,
        ], 'booking.fee.strategies');

        // Bind FeeCalculator to tagged strategies
        $this->app->bind(FeeCalculator::class, function ($app) {
            return new FeeCalculator($app->tagged('booking.fee.strategies'));
        });
    }
}

