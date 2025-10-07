<?php

namespace App\Services\ServiceManagement;

use App\Models\Service;
use App\Models\User;
use App\Services\ServiceManagement\Contracts\ServiceUpdateStrategy;
use App\Services\ServiceManagement\Strategies\PropertyUpdateStrategy;
use App\Services\ServiceManagement\Strategies\EventUpdateStrategy;
use App\Services\ServiceManagement\Strategies\RestaurantUpdateStrategy;
use App\Services\ServiceManagement\Strategies\CateringUpdateStrategy;
use App\Services\ServiceCreation\ServiceFactory;
use Illuminate\Support\Facades\Log;

class UnifiedServiceManager
{
    private array $updateStrategies = [];
    private ServiceFactory $serviceFactory;

    public function __construct(
        PropertyUpdateStrategy $propertyStrategy,
        EventUpdateStrategy $eventStrategy,
        RestaurantUpdateStrategy $restaurantStrategy,
        CateringUpdateStrategy $cateringStrategy,
        ServiceFactory $serviceFactory
    ) {
        $this->serviceFactory = $serviceFactory;
        
        $this->updateStrategies = [
            'property' => $propertyStrategy,
            'event' => $eventStrategy,
            'restaurant' => $restaurantStrategy,
            'catering' => $cateringStrategy,
        ];
    }

    /**
     * Create a new service using the appropriate strategy
     *
     * @param array $data
     * @param User $user
     * @return Service
     */
    public function createService(array $data, User $user): Service
    {
        Log::info("Creating service using UnifiedServiceManager", [
            'user_id' => $user->id,
            'service_type' => $this->detectServiceType($data)
        ]);

        return $this->serviceFactory->createService($data);
    }

    /**
     * Update an existing service using the appropriate strategy
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function updateService(Service $service, array $data): Service
    {
        $serviceType = $this->detectServiceTypeFromService($service);
        
        if (!isset($this->updateStrategies[$serviceType])) {
            throw new \InvalidArgumentException("No update strategy found for service type: {$serviceType}");
        }

        $strategy = $this->updateStrategies[$serviceType];
        
        // Validate data using the specific strategy
        $validationErrors = $strategy->validateData($data);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $validationErrors));
        }

        Log::info("Updating service using strategy", [
            'service_id' => $service->id,
            'service_type' => $serviceType,
            'strategy' => get_class($strategy)
        ]);

        return $strategy->update($service, $data);
    }

    /**
     * Get user services with proper relationships loaded
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserServices(User $user)
    {
        return $user->services()
            ->with(['category', 'country', 'currency', 'property', 'event', 'restaurant', 'catering.items'])
            ->latest()
            ->get();
    }

    /**
     * Get a single service with all relationships
     *
     * @param Service $service
     * @return Service
     */
    public function getService(Service $service): Service
    {
        return $service->load(['category', 'country', 'currency', 'event', 'property', 'restaurant', 'catering.items']);
    }

    /**
     * Delete a service using the appropriate strategy
     *
     * @param Service $service
     * @return void
     */
    public function deleteService(Service $service): void
    {
        $serviceType = $this->detectServiceTypeFromService($service);
        
        if (!isset($this->updateStrategies[$serviceType])) {
            // Fallback to direct deletion
            $service->delete();
            return;
        }

        $strategy = $this->updateStrategies[$serviceType];
        
        Log::info("Deleting service using strategy", [
            'service_id' => $service->id,
            'service_type' => $serviceType,
            'strategy' => get_class($strategy)
        ]);

        // For now, we'll use the existing services for deletion
        // This can be extended to have a delete method in strategies
        $this->deleteServiceUsingExistingServices($service, $serviceType);
    }

    /**
     * Detect service type from request data
     *
     * @param array $data
     * @return string
     */
    private function detectServiceType(array $data): string
    {
        return $this->serviceFactory->detectServiceType($data);
    }

    /**
     * Detect service type from existing service
     *
     * @param Service $service
     * @return string
     */
    private function detectServiceTypeFromService(Service $service): string
    {
        if ($service->property) return 'property';
        if ($service->event) return 'event';
        if ($service->restaurant) return 'restaurant';
        if ($service->catering) return 'catering';
        
        throw new \InvalidArgumentException('Unknown service type for service ID: ' . $service->id);
    }

    /**
     * Delete service using existing services (temporary solution)
     *
     * @param Service $service
     * @param string $serviceType
     * @return void
     */
    private function deleteServiceUsingExistingServices(Service $service, string $serviceType): void
    {
        // This is a temporary solution until we add delete methods to strategies
        // We'll use the existing services for now
        switch ($serviceType) {
            case 'property':
                app(\App\Services\Contracts\IPropertyService::class)->deleteProperty($service);
                break;
            case 'event':
                app(\App\Services\Contracts\IEventService::class)->deleteEvent($service);
                break;
            case 'restaurant':
                app(\App\Services\Contracts\IRestaurantService::class)->deleteRestaurant($service);
                break;
            case 'catering':
                app(\App\Services\Contracts\ICateringService::class)->deleteCatering($service);
                break;
            default:
                $service->delete();
        }
    }
}
