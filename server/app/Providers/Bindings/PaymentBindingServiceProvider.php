<?php

namespace App\Providers\Bindings;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Services\PaymentService;

class PaymentBindingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, PaymentService::class);
    }
}

