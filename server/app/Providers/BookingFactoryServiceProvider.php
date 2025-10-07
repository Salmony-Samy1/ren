<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Booking\Contracts\IOrderFactory;
use App\Services\Booking\Contracts\IInvoiceFactory;
use App\Services\Booking\OrderFactory;
use App\Services\Booking\InvoiceFactory;

class BookingFactoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOrderFactory::class, OrderFactory::class);
        $this->app->bind(IInvoiceFactory::class, InvoiceFactory::class);
    }
}

