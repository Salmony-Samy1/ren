<?php

namespace App\Domain\Booking\Pricing\Strategies;

use App\Domain\Booking\Pricing\FeeCalculationStrategy;
use App\Models\Service;

class CateringFeeStrategy implements FeeCalculationStrategy
{
    public function supports(Service $service): bool
    {
        return (bool) ($service->catering || $service->cateringItem);
    }

    public function subtotal(Service $service, array $details): float
    {
        $basePrice = (float)($service->price_amount ?? 0);
        if ($basePrice <= 0 && $service->cateringItem) {
            $basePrice = (float) ($service->cateringItem->price ?? 0);
        }
        $numberOfItems = max(1, (int)($details['number_of_items'] ?? 1));
        $subtotal = $basePrice * $numberOfItems;

        // add-ons optional inline pricing
        if (!empty($details['add_ons']) && is_array($details['add_ons'])) {
            foreach ($details['add_ons'] as $ao) {
                $itemId = (int)($ao['id'] ?? 0);
                $qty = max(0, (int)($ao['qty'] ?? 0));
                if ($itemId > 0 && $qty > 0) {
                    // Prefer matching by catering_id, fallback to legacy service_id linkage
                    $addon = \App\Models\CateringItem::where('id', $itemId)
                        ->where('catering_id', optional($service->catering)->id)
                        ->first();
                    if (!$addon) {
                        $addon = \App\Models\CateringItem::where('id', $itemId)
                            ->where(function($q) use ($service) {
                                $q->whereNull('catering_id')->orWhere('service_id', $service->id);
                            })->first();
                    }
                    if ($addon) {
                        $subtotal += ((float) ($addon->price ?? 0)) * $qty;
                    }
                }
            }
        }

        return $subtotal;
    }
}

