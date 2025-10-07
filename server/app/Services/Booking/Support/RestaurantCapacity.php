<?php

namespace App\Services\Booking\Support;

use App\Models\Booking;
use App\Models\Service;

class RestaurantCapacity
{
    /**
     * Validate restaurant capacity for a given day (VIP vs Standard) under transaction with locks.
     * Throws \RuntimeException on capacity violations.
     */
    public static function validate(Service $lockedService, \Carbon\Carbon $start, array $details): void
    {
        if (!$lockedService->restaurant) { return; }
        
        $day = $start->copy()->startOfDay();
        $dayEnd = $start->copy()->endOfDay();
        $sameDay = Booking::where('service_id', $lockedService->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_date', [$day, $dayEnd])
            ->lockForUpdate()
            ->get();

        $tableId = $details['table_id'] ?? null;
        $table = null;
        
        if ($tableId) {
            $table = \App\Models\RestaurantTable::where('id', $tableId)
                ->where('restaurant_id', $lockedService->restaurant->id)
                ->first();
        }
        
        if ($table && $table->type === 'VIP') {
            // VIP table validation: count bookings for this specific table
            $used = $sameDay->filter(fn($b) => ((int)($b->booking_details['table_id'] ?? 0)) === (int)$table->id)->count();
            $cap = (int)($table->quantity ?? 0);
            if (($used + 1) > $cap) {
                throw new \RuntimeException('VIP table type fully booked for the selected day');
            }
        } else {
            // Normal table validation: check total people capacity
            $reqPeople = (int)($details['number_of_people'] ?? 1);
            $usedPeople = $sameDay->sum(fn($b) => (int)($b->booking_details['number_of_people'] ?? 0));
            
            // Use the new accessor for better performance and single source of truth
            $normalTables = $lockedService->restaurant->tables()->where('type', 'Normal')->get();
            $capPeople = $normalTables->sum(fn($t) => ((int)$t->quantity) * ((int)$t->capacity_people));
            
            if ($capPeople <= 0 || ($usedPeople + $reqPeople) > $capPeople) {
                throw new \RuntimeException('Standard tables capacity exceeded for the selected day');
            }
        }
    }

    /**
     * Compute restaurant capacity preview stats (non-locking) for UI.
     */
    public static function preview(Service $service, \Carbon\Carbon $start, array $details): array
    {
        $day = $start->copy()->startOfDay();
        $dayEnd = $start->copy()->endOfDay();
        $sameDay = Booking::where('service_id', $service->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_date', [$day, $dayEnd])
            ->get();
            
        $req = (int)($details['number_of_people'] ?? 1);
        $tableId = $details['table_id'] ?? null;
        $table = null;
        
        if ($tableId) {
            $table = \App\Models\RestaurantTable::where('id', $tableId)
                ->where('restaurant_id', $service->restaurant->id)->first();
        }
        
        if ($table && $table->type === 'VIP') {
            // VIP table preview: count bookings for this specific table
            $used = $sameDay->filter(fn($b) => ((int)($b->booking_details['table_id'] ?? 0)) === (int)$table->id)->count();
            $cap = (int)($table->quantity ?? 0);
            return ['used' => $used, 'requested' => 1, 'capacity' => $cap, 'remaining' => max(0, $cap - $used)];
        } else {
            // Normal table preview: check total people capacity
            $usedPeople = $sameDay->sum(fn($b) => (int)($b->booking_details['number_of_people'] ?? 0));
            
            // Use the new accessor for better performance and single source of truth
            $normalTables = $service->restaurant->tables()->where('type', 'Normal')->get();
            $capPeople = $normalTables->sum(fn($t) => ((int)$t->quantity) * ((int)$t->capacity_people));
            
            return ['used' => $usedPeople, 'requested' => $req, 'capacity' => $capPeople, 'remaining' => max(0, $capPeople - $usedPeople)];
        }
    }
}

