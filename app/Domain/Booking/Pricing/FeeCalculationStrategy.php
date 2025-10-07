<?php

namespace App\Domain\Booking\Pricing;

use App\Models\Service;

interface FeeCalculationStrategy
{
    /**
     * Whether this strategy can price the given service.
     */
    public function supports(Service $service): bool;

    /**
     * Compute the service subtotal (before tax/commission/discounts) based on details.
     * Implementations must not apply tax/commission/coupons/points.
     *
     * @param Service $service
     * @param array $details
     * @return float  The computed subtotal amount
     */
    public function subtotal(Service $service, array $details): float;
}

