<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Service;
use Illuminate\Support\Collection;

class CartAvailabilityService
{
    public function __construct(private readonly BookingService $booking)
    {
    }

    /**
     * Validate availability per item and cross-item consistency (e.g., overlapping times if required).
     */
    public function validate(Cart $cart): array
    {
        $errors = [];
        foreach ($cart->items as $item) {
            if ($item->start_date && $item->end_date) {
                $service = $item->service;
                if (!$this->booking->isServiceAvailable($service, $item->start_date->toDateTimeString(), $item->end_date->toDateTimeString())) {
                    $errors[] = [
                        'item_id' => $item->id,
                        'service_id' => $item->service_id,
                        'message' => 'Service not available in selected time window',
                    ];
                }
            }
        }
        // TODO: add cross-item consistency rules (e.g., same date for catering and property)
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

