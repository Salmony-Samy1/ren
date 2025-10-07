<?php

namespace App\Services\ServiceCreation\Strategies;

use App\Models\Service;
use App\Services\ServiceCreation\Contracts\IServiceCreationStrategy;
use App\Services\CateringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CateringServiceStrategy implements IServiceCreationStrategy
{
    private CateringService $cateringService;
    
    public function __construct(CateringService $cateringService)
    {
        $this->cateringService = $cateringService;
    }
    
    public function createService(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            try {
                // Convert flat catering fields to nested structure if needed
                $isFormDataStructure = isset($data['catering_name']) || isset($data['cuisine_type']) || isset($data['min_order_amount']);
                
                if ($isFormDataStructure && !isset($data['catering'])) {
                    $data['catering'] = [
                        'catering_name' => $data['catering_name'] ?? '',
                        'cuisine_type' => $data['cuisine_type'] ?? '',
                        'min_order_amount' => $data['min_order_amount'] ?? '',
                        'description' => $data['description'] ?? '',
                        'max_order_amount' => $data['max_order_amount'] ?? null,
                        'available_stock' => $data['available_stock'] ?? null,
                        'preparation_time' => $data['preparation_time'] ?? null,
                        'delivery_available' => $data['delivery_available'] ?? null,
                        'pickup_available' => $data['pickup_available'] ?? null,
                        'on_site_available' => $data['on_site_available'] ?? null,
                        'cancellation_policy' => $data['cancellation_policy'] ?? '',
                        'fulfillment_methods' => $data['fulfillment_methods'] ?? null,
                    ];
                }
                
                Log::info("Creating catering service with data structure", [
                    'has_catering_nested' => isset($data['catering']),
                    'has_catering_name_top' => isset($data['catering_name']),
                    'has_catering_items' => isset($data['catering_items']),
                    'catering_structure' => $data['catering'] ?? 'N/A',
                    'catering_items_data' => $data['catering_items'] ?? 'N/A'
                ]);
                
                $service = $this->cateringService->createCatering($data);
                Log::info("Catering service created successfully", [
                    'service_id' => $service->id,
                    'user_id' => $service->user_id
                ]);
                return $service;
            } catch (\Exception $e) {
                Log::error("Failed to create catering service", [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                throw $e;
            }
        });
    }
    
    public function validateData(array $data): array
    {
        // Delegate validation to Laravel's built-in validation rules
        // Let StoreServiceRequest and StoreCateringServiceRequest handle all validation
        Log::info('CateringServiceStrategy::validateData - Delegating to Laravel validation', [
            'data_keys' => array_keys($data),
            'has_catering_nested' => isset($data['catering'])
        ]);
        
        return []; // No custom validation errors - Laravel handles it
    }
    
    public function getFormRequest(): string
    {
        return \App\Http\Requests\StoreCateringServiceRequest::class;
    }
    
    public function getServiceType(): string
    {
        return 'catering';
    }
}