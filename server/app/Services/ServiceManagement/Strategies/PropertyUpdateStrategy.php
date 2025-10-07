<?php

namespace App\Services\ServiceManagement\Strategies;

use App\Models\Service;
use App\Services\ServiceManagement\Contracts\ServiceUpdateStrategy;
use App\Services\Contracts\IPropertyService;
use Illuminate\Support\Facades\Log;

class PropertyUpdateStrategy implements ServiceUpdateStrategy
{
    public function __construct(
        private readonly IPropertyService $propertyService
    ) {}

    /**
     * Update a property service
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function update(Service $service, array $data): Service
    {
        Log::info("Updating property service", [
            'service_id' => $service->id,
            'has_property_data' => isset($data['property']),
            'has_media' => $this->hasMediaFiles($data)
        ]);

        return $this->propertyService->updateProperty($service, $data);
    }

    /**
     * Validate property-specific data
     *
     * @param array $data
     * @return array
     */
    public function validateData(array $data): array
    {
        $errors = [];

        // Check if this is a property update request
        if (!$this->isPropertyUpdateRequest($data)) {
            $errors[] = 'Invalid property update request';
            return $errors;
        }

        // Validate property-specific fields if provided
        if (isset($data['property'])) {
            $propertyData = $data['property'];
            
            // Validate required fields
            if (isset($propertyData['nightly_price']) && $propertyData['nightly_price'] < 0) {
                $errors[] = 'Nightly price must be non-negative';
            }

            if (isset($propertyData['max_adults']) && $propertyData['max_adults'] < 1) {
                $errors[] = 'Maximum adults must be at least 1';
            }

            if (isset($propertyData['max_children']) && $propertyData['max_children'] < 0) {
                $errors[] = 'Maximum children must be non-negative';
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
        return 'property';
    }

    /**
     * Check if this is a property update request
     *
     * @param array $data
     * @return bool
     */
    private function isPropertyUpdateRequest(array $data): bool
    {
        return isset($data['property']) || 
               $this->hasPropertyMediaFiles($data);
    }

    /**
     * Check if request has media files
     *
     * @param array $data
     * @return bool
     */
    private function hasMediaFiles(array $data): bool
    {
        $request = request();
        return $request->hasFile('images') || 
               $request->hasFile('videos') ||
               $request->hasFile('property.images') ||
               $request->hasFile('property.videos');
    }

    /**
     * Check if request has property-specific media files
     *
     * @param array $data
     * @return bool
     */
    private function hasPropertyMediaFiles(array $data): bool
    {
        $request = request();
        return $request->hasFile('images') || 
               $request->hasFile('videos') ||
               $request->hasFile('property.images') ||
               $request->hasFile('property.videos') ||
               $request->hasFile('bedroom_photos') ||
               $request->hasFile('kitchen_photos') ||
               $request->hasFile('pool_photos') ||
               $request->hasFile('bathroom_photos') ||
               $request->hasFile('living_room_photos');
    }
}

