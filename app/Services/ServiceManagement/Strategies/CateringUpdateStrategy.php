<?php

namespace App\Services\ServiceManagement\Strategies;

use App\Models\Service;
use App\Services\ServiceManagement\Contracts\ServiceUpdateStrategy;
use App\Services\Contracts\ICateringService;
use Illuminate\Support\Facades\Log;

class CateringUpdateStrategy implements ServiceUpdateStrategy
{
    public function __construct(
        private readonly ICateringService $cateringService
    ) {}

    /**
     * Update a catering service
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function update(Service $service, array $data): Service
    {
        Log::info("Updating catering service", [
            'service_id' => $service->id,
            'has_catering_data' => isset($data['catering']),
            'has_catering_item_data' => isset($data['catering_item']),
            'has_media' => $this->hasMediaFiles($data)
        ]);

        return $this->cateringService->updateCatering($service, $data);
    }

    /**
     * Validate catering-specific data
     *
     * @param array $data
     * @return array
     */
    public function validateData(array $data): array
    {
        $errors = [];

        // Check if this is a catering update request
        if (!$this->isCateringUpdateRequest($data)) {
            $errors[] = 'Invalid catering update request';
            return $errors;
        }

        // Validate catering-specific fields if provided
        if (isset($data['catering'])) {
            $cateringData = $data['catering'];
            
            // Validate required fields
            if (isset($cateringData['available_stock']) && $cateringData['available_stock'] < 0) {
                $errors[] = 'Available stock must be non-negative';
            }
        }

        // Validate catering item fields if provided
        if (isset($data['catering_item'])) {
            $cateringItemData = $data['catering_item'];
            
            if (isset($cateringItemData['packages']) && is_array($cateringItemData['packages'])) {
                foreach ($cateringItemData['packages'] as $index => $package) {
                    if (isset($package['price']) && $package['price'] < 0) {
                        $errors[] = "Package {$index} price must be non-negative";
                    }
                    
                    if (isset($package['available_stock']) && $package['available_stock'] < 0) {
                        $errors[] = "Package {$index} available stock must be non-negative";
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
        return 'catering';
    }

    /**
     * Check if this is a catering update request
     *
     * @param array $data
     * @return bool
     */
    private function isCateringUpdateRequest(array $data): bool
    {
        return isset($data['catering']) || 
               isset($data['catering_item']) ||
               $this->hasMediaFiles($data);
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
               $request->hasFile('catering.images') ||
               $request->hasFile('catering.videos');
    }
}

