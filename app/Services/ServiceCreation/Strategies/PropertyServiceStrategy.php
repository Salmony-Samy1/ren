<?php

namespace App\Services\ServiceCreation\Strategies;

use App\Models\Service;
use App\Services\ServiceCreation\Contracts\IServiceCreationStrategy;
use App\Services\PropertyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyServiceStrategy implements IServiceCreationStrategy
{
    private PropertyService $propertyService;
    
    public function __construct(PropertyService $propertyService)
    {
        $this->propertyService = $propertyService;
    }
    
    public function createService(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            try {
                $service = $this->propertyService->createProperty($data);
                Log::info("Property service created successfully", [
                    'service_id' => $service->id,
                    'user_id' => $service->user_id
                ]);
                return $service;
            } catch (\Exception $e) {
                Log::error("Failed to create property service", [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                throw $e;
            }
        });
    }
    
    public function validateData(array $data): array
    {
        $errors = [];
        
        if (!isset($data['property'])) {
            $errors[] = 'Property data is required';
        }
        
        if (isset($data['property'])) {
            $property = $data['property'];
            
            if (empty($property['property_name'])) {
                $errors[] = 'Property name is required';
            }
            
            if (empty($property['type'])) {
                $errors[] = 'Property type is required';
            }
            
            if (empty($property['category'])) {
                $errors[] = 'Property category is required';
            }
        }
        
        return $errors;
    }
    
    public function getFormRequest(): string
    {
        return \App\Http\Requests\StorePropertyServiceRequest::class;
    }
    
    public function getServiceType(): string
    {
        return 'property';
    }
}
