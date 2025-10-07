<?php

namespace App\Services\ServiceCreation\Strategies;

use App\Models\Service;
use App\Services\ServiceCreation\Contracts\IServiceCreationStrategy;
use App\Services\RestaurantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantServiceStrategy implements IServiceCreationStrategy
{
    private RestaurantService $restaurantService;
    
    public function __construct(RestaurantService $restaurantService)
    {
        $this->restaurantService = $restaurantService;
    }
    
    public function createService(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            try {
                $service = $this->restaurantService->createRestaurant($data);
                Log::info("Restaurant service created successfully", [
                    'service_id' => $service->id,
                    'user_id' => $service->user_id
                ]);
                return $service;
            } catch (\Exception $e) {
                Log::error("Failed to create restaurant service", [
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
        
        if (!isset($data['restaurant'])) {
            $errors[] = 'Restaurant data is required';
        }
        
        if (isset($data['restaurant'])) {
            $restaurant = $data['restaurant'];
            
            if (empty($restaurant['restaurant_name'])) {
                $errors[] = 'Restaurant name is required';
            }
            
            if (empty($restaurant['cuisine_type'])) {
                $errors[] = 'Cuisine type is required';
            }
            
            if (empty($restaurant['description'])) {
                $errors[] = 'Restaurant description is required';
            }
        }
        
        return $errors;
    }
    
    public function getFormRequest(): string
    {
        return \App\Http\Requests\StoreRestaurantServiceRequest::class;
    }
    
    public function getServiceType(): string
    {
        return 'restaurant';
    }
}
