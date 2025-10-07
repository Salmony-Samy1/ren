<?php

namespace App\Domain\Booking\Pricing\Strategies;

use App\Domain\Booking\Pricing\FeeCalculationStrategy;
use App\Models\Service;

class RestaurantFeeStrategy implements FeeCalculationStrategy
{
    public function supports(Service $service): bool
    {
        return (bool) $service->restaurant;
    }

    public function subtotal(Service $service, array $details): float
    {
        $numberOfPeople = max(1, (int)($details['number_of_people'] ?? 1));
        $tableCost = 0.0;

        if (!empty($details['table_id'])) {
            // Specific table booking (VIP or specific Normal table)
            $table = \App\Models\RestaurantTable::where('id', $details['table_id'])
                ->where('restaurant_id', $service->restaurant->id)
                ->first();
            
            if ($table) {
                if ($table->type === 'VIP' && isset($table->price_per_table) && (float)$table->price_per_table > 0) {
                    // VIP table: fixed price per table regardless of people count
                    $tableCost = (float) $table->price_per_table;
                } else {
                    // Normal table: price per person
                    $tableCost = (float)($table->price_per_person ?? 0) * $numberOfPeople;
                }
            } else {
                // Invalid table_id: fallback to default per-person pricing
                $tableCost = $service->restaurant->default_price_per_person * $numberOfPeople;
            }
        } else {
            // No specific table: use default per-person pricing from Normal tables
            $tableCost = $service->restaurant->default_price_per_person * $numberOfPeople;
        }

        $menuItemsCost = 0.0;
        if (!empty($details['menu_items']) && is_array($details['menu_items'])) {
            foreach ($details['menu_items'] as $itemData) {
                $menuItem = \App\Models\RestaurantMenuItem::find($itemData['item_id'] ?? 0);
                if ($menuItem && $menuItem->restaurant_id === $service->restaurant->id) {
                    $menuItemsCost += (float) $menuItem->price * (int)($itemData['quantity'] ?? 1);
                }
            }
        }

        return $tableCost + $menuItemsCost;
    }
}

