<?php

namespace App\Services\ServiceManagement\Strategies;

use App\Models\Service;
use App\Services\ServiceManagement\Contracts\ServiceUpdateStrategy;
use App\Services\Contracts\IRestaurantService;
use Illuminate\Support\Facades\Log;

class RestaurantUpdateStrategy implements ServiceUpdateStrategy
{
    public function __construct(
        private readonly IRestaurantService $restaurantService
    ) {}

    /**
     * Update a restaurant service
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function update(Service $service, array $data): Service
    {
        Log::info("Updating restaurant service", [
            'service_id' => $service->id,
            'has_restaurant_data' => isset($data['restaurant'])
        ]);

        return $this->restaurantService->updateRestaurant($service, $data);
    }

    /**
     * Validate restaurant-specific data
     *
     * @param array $data
     * @return array
     */
    public function validateData(array $data): array
    {
        $errors = [];

        // Check if this is a restaurant update request
        if (!$this->isRestaurantUpdateRequest($data)) {
            $errors[] = 'Invalid restaurant update request';
            return $errors;
        }

        // Validate restaurant-specific fields if provided
        if (isset($data['restaurant'])) {
            $restaurantData = $data['restaurant'];
            
            // Validate required fields
            if (isset($restaurantData['daily_available_bookings']) && $restaurantData['daily_available_bookings'] < 1) {
                $errors[] = 'Daily available bookings must be at least 1';
            }

            if (isset($restaurantData['grace_period_minutes']) && $restaurantData['grace_period_minutes'] < 0) {
                $errors[] = 'Grace period minutes must be non-negative';
            }

            // Validate working hours format if provided
            if (isset($restaurantData['working_hours'])) {
                $workingHours = $restaurantData['working_hours'];
                if (is_string($workingHours)) {
                    $decoded = json_decode($workingHours, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = 'Working hours must be valid JSON';
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get the service type this strategy handles
     *
     * @return string
     */
    public function getServiceType(): string
    {
        return 'restaurant';
    }

    /**
     * Check if this is a restaurant update request
     *
     * @param array $data
     * @return bool
     */
    private function isRestaurantUpdateRequest(array $data): bool
    {
        return isset($data['restaurant']);
    }
}

