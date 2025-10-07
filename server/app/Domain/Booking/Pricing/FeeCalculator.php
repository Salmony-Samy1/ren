<?php

namespace App\Domain\Booking\Pricing;

use App\Models\Service;

class FeeCalculator
{
    /** @var iterable<FeeCalculationStrategy> */
    private iterable $strategies;

    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function subtotal(Service $service, array $details): float
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($service)) {
                return $strategy->subtotal($service, $details);
            }
        }
        // Fallback: zero subtotal
        return 0.0;
    }
}

