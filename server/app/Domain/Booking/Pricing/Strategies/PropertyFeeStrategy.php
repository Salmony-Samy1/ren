<?php

namespace App\Domain\Booking\Pricing\Strategies;

use App\Domain\Booking\Pricing\FeeCalculationStrategy;
use App\Models\Service;
use Carbon\Carbon;

class PropertyFeeStrategy implements FeeCalculationStrategy
{
    public function supports(Service $service): bool
    {
        return (bool) $service->property;
    }

    public function subtotal(Service $service, array $details): float
    {
        $basePrice = (float) ($service->property->nightly_price ?? 0);
        if (!isset($details['number_of_nights'])) {
            try {
                $start = Carbon::parse($details['start_date'] ?? ($details['booking_start'] ?? ''));
                $end = Carbon::parse($details['end_date'] ?? ($details['booking_end'] ?? ''));
                $nights = max(1, $start->diffInDays($end));
            } catch (\Throwable $e) {
                $nights = 1;
            }
        } else {
            $nights = max(1, (int) $details['number_of_nights']);
        }
        return $basePrice * $nights;
    }
}

