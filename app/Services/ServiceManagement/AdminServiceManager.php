<?php

namespace App\Services\ServiceManagement;

use App\Models\Service;
use App\Models\User;
use App\Services\ServiceManagement\UnifiedServiceManager;
use Illuminate\Support\Facades\Log;

class AdminServiceManager
{
    public function __construct(
        private readonly UnifiedServiceManager $serviceManager
    ) {}

    /**
     * Create a service on behalf of a provider (Admin function)
     *
     * @param array $data
     * @param int $providerId
     * @return Service
     */
    public function createServiceForProvider(array $data, int $providerId): Service
    {
        $provider = User::findOrFail($providerId);
        
        Log::info("Admin creating service for provider", [
            'provider_id' => $providerId,
            'service_type' => $this->detectServiceType($data),
            'admin_id' => auth()->id()
        ]);

        // Add provider_id to data
        $data['user_id'] = $providerId;
        
        // Transform flat data to nested structure for ServiceFactory
        $transformedData = $this->transformDataForServiceFactory($data);
        
        return $this->serviceManager->createService($transformedData, $provider);
    }

    /**
     * Update a service (Admin function)
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function updateService(Service $service, array $data): Service
    {
        Log::info("Admin updating service", [
            'service_id' => $service->id,
            'service_type' => $this->getServiceType($service),
            'admin_id' => auth()->id()
        ]);

        return $this->serviceManager->updateService($service, $data);
    }

    /**
     * Delete a service (Admin function)
     *
     * @param Service $service
     * @return void
     */
    public function deleteService(Service $service): void
    {
        Log::info("Admin deleting service", [
            'service_id' => $service->id,
            'service_type' => $this->getServiceType($service),
            'admin_id' => auth()->id()
        ]);

        $this->serviceManager->deleteService($service);
    }

    /**
     * Get service with all relationships
     *
     * @param Service $service
     * @return Service
     */
    public function getService(Service $service): Service
    {
        return $this->serviceManager->getService($service);
    }

    /**
     * Get all services for a specific provider
     *
     * @param int $providerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProviderServices(int $providerId)
    {
        $provider = User::findOrFail($providerId);
        return $this->serviceManager->getUserServices($provider);
    }

    /**
     * Transform flat admin data to nested structure for ServiceFactory
     *
     * @param array $data
     * @return array
     */
    private function transformDataForServiceFactory(array $data): array
    {
        $serviceType = $this->detectServiceType($data);
        
        switch ($serviceType) {
            case 'event':
                return $this->transformEventData($data);
            case 'property':
                return $this->transformPropertyData($data);
            case 'restaurant':
                return $this->transformRestaurantData($data);
            case 'catering':
                return $this->transformCateringData($data);
            default:
                return $data;
        }
    }

    /**
     * Transform event data to nested structure
     */
    private function transformEventData(array $data): array
    {
        $eventData = [];
        
        // Map flat event fields to nested structure
        $eventFields = [
            'event_name', 'description', 'language', 'max_individuals',
            'start_at', 'end_at', 'gender_type', 'hospitality_available',
            'price_per_person', 'price_currency_id', 'cancellation_policy',
            'meeting_point', 'pricing_type', 'base_price', 'discount_price'
        ];
        
        foreach ($eventFields as $field) {
            if (isset($data[$field])) {
                $eventData[$field] = $data[$field];
            }
        }
        
        // Add default values for required fields
        $eventData['pricing_type'] = $eventData['pricing_type'] ?? 'fixed';
        $eventData['base_price'] = $eventData['base_price'] ?? $eventData['price_per_person'] ?? 0;
        $eventData['meeting_point'] = $eventData['meeting_point'] ?? '';
        
        // Add media if present
        if (isset($data['images'])) {
            $eventData['images'] = $data['images'];
        }
        if (isset($data['videos'])) {
            $eventData['videos'] = $data['videos'];
        }
        
        return [
            'name' => $data['event_name'] ?? 'Event Service',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'place_id' => $data['place_id'] ?? '',
            'gender_type' => $data['gender_type'] ?? 'both',
            'price_currency_id' => $data['price_currency_id'] ?? 1,
            'price_amount' => $data['price_per_person'] ?? 0,
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'category_id' => $data['category_id'] ?? 1,
            'country_id' => $data['country_id'] ?? 1, // Default to Saudi Arabia
            'user_id' => $data['user_id'],
            'event' => $eventData,
            'images' => $data['images'] ?? [],
            'videos' => $data['videos'] ?? []
        ];
    }

    /**
     * Transform property data to nested structure
     */
    private function transformPropertyData(array $data): array
    {
        $propertyData = [];
        
        // Map flat property fields to nested structure
        $propertyFields = [
            'property_name', 'type', 'category', 'area_sqm', 'nightly_price',
            'max_adults', 'max_children', 'child_free_until_age', 'bedrooms',
            'kitchens', 'pools', 'bathrooms', 'livingRooms', 'facilities'
        ];
        
        foreach ($propertyFields as $field) {
            if (isset($data[$field])) {
                $propertyData[$field] = $data[$field];
            }
        }
        
        return [
            'name' => $data['name'] ?? 'Property Service',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'place_id' => $data['place_id'] ?? '',
            'gender_type' => $data['gender_type'] ?? 'both',
            'price_currency_id' => $data['price_currency_id'] ?? 1,
            'price_amount' => $data['price_amount'] ?? 0,
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'category_id' => $data['category_id'] ?? 1,
            'country_id' => $data['country_id'] ?? 1, // Default to Saudi Arabia
            'user_id' => $data['user_id'],
            'property' => $propertyData,
            'images' => $data['images'] ?? [],
            'videos' => $data['videos'] ?? []
        ];
    }

    /**
     * Transform restaurant data to nested structure
     */
    private function transformRestaurantData(array $data): array
    {
        $restaurantData = [];
        
        // Map flat restaurant fields to nested structure
        $restaurantFields = [
            'restaurant_name', 'cuisine_type', 'description', 'capacity',
            'operating_hours', 'booking_hours', 'amenities'
        ];
        
        foreach ($restaurantFields as $field) {
            if (isset($data[$field])) {
                $restaurantData[$field] = $data[$field];
            }
        }
        
        return [
            'name' => $data['name'] ?? 'Restaurant Service',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'place_id' => $data['place_id'] ?? '',
            'gender_type' => $data['gender_type'] ?? 'both',
            'price_currency_id' => $data['price_currency_id'] ?? 1,
            'price_amount' => $data['price_amount'] ?? 0,
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'category_id' => $data['category_id'] ?? 1,
            'country_id' => $data['country_id'] ?? 1, // Default to Saudi Arabia
            'user_id' => $data['user_id'],
            'restaurant' => $restaurantData,
            'images' => $data['images'] ?? [],
            'videos' => $data['videos'] ?? []
        ];
    }

    /**
     * Transform catering data to nested structure
     */
    private function transformCateringData(array $data): array
    {
        $cateringData = [];
        
        // Map flat catering fields to nested structure
        $cateringFields = [
            'catering_name', 'cuisine_type', 'description', 'min_order_amount',
            'max_order_amount', 'available_stock', 'preparation_time',
            'delivery_available', 'delivery_radius_km', 'fulfillment_methods'
        ];
        
        foreach ($cateringFields as $field) {
            if (isset($data[$field])) {
                $cateringData[$field] = $data[$field];
            }
        }
        
        // Add default values for required fields
        $cateringData['catering_name'] = $cateringData['catering_name'] ?? $data['name'] ?? 'Catering Service';
        $cateringData['cuisine_type'] = $cateringData['cuisine_type'] ?? 'عربي';
        $cateringData['min_order_amount'] = $cateringData['min_order_amount'] ?? $data['price_amount'] ?? 0;
        
        return [
            'name' => $data['name'] ?? 'Catering Service',
            'description' => $data['description'] ?? '',
            'address' => $data['address'] ?? '',
            'latitude' => $data['latitude'] ?? 0,
            'longitude' => $data['longitude'] ?? 0,
            'place_id' => $data['place_id'] ?? '',
            'gender_type' => $data['gender_type'] ?? 'both',
            'price_currency_id' => $data['price_currency_id'] ?? 1,
            'price_amount' => $data['price_amount'] ?? 0,
            'available_from' => $data['available_from'] ?? null,
            'available_to' => $data['available_to'] ?? null,
            'category_id' => $data['category_id'] ?? 1,
            'country_id' => $data['country_id'] ?? 1, // Default to Saudi Arabia
            'user_id' => $data['user_id'],
            'catering' => $cateringData,
            'catering_item' => $data['catering_item'] ?? [],
            'images' => $data['images'] ?? [],
            'videos' => $data['videos'] ?? []
        ];
    }

    /**
     * Detect service type from data
     *
     * @param array $data
     * @return string
     */
    private function detectServiceType(array $data): string
    {
        // Check for nested structure first
        if (isset($data['property'])) return 'property';
        if (isset($data['event'])) return 'event';
        if (isset($data['restaurant'])) return 'restaurant';
        if (isset($data['catering']) || isset($data['catering_item'])) return 'catering';
        
        // Check for flat structure (admin requests)
        if (isset($data['event_name'])) return 'event';
        if (isset($data['property_name'])) return 'property';
        if (isset($data['restaurant_name'])) return 'restaurant';
        if (isset($data['catering_name'])) return 'catering';
        
        return 'unknown';
    }

    /**
     * Get service type from service model
     *
     * @param Service $service
     * @return string
     */
    private function getServiceType(Service $service): string
    {
        if ($service->property) return 'property';
        if ($service->event) return 'event';
        if ($service->restaurant) return 'restaurant';
        if ($service->catering) return 'catering';
        
        return 'unknown';
    }
}
