<?php

namespace App\Domain\Booking\Pricing\Strategies;

use App\Domain\Booking\Pricing\FeeCalculationStrategy;
use App\Models\Service;

class EventFeeStrategy implements FeeCalculationStrategy
{
    public function supports(Service $service): bool
    {
        return (bool) $service->event;
    }

    public function subtotal(Service $service, array $details): float
    {
        $basePrice = (float) ($service->event->base_price ?? 0);
        $numberOfPeople = max(1, (int)($details['number_of_people'] ?? 1));
        return $basePrice * $numberOfPeople;
    }
}

