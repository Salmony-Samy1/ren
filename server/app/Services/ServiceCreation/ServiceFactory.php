<?php

namespace App\Services\ServiceCreation;

use App\Models\Service;
use App\Services\ServiceCreation\Contracts\IServiceFactory;
use App\Services\ServiceCreation\Contracts\IServiceCreationStrategy;
use App\Services\ServiceCreation\Strategies\PropertyServiceStrategy;
use App\Services\ServiceCreation\Strategies\EventServiceStrategy;
use App\Services\ServiceCreation\Strategies\RestaurantServiceStrategy;
use App\Services\ServiceCreation\Strategies\CateringServiceStrategy;
use Illuminate\Support\Facades\Log;

class ServiceFactory implements IServiceFactory
{
    private array $strategies = [];
    
    public function __construct(
        PropertyServiceStrategy $propertyStrategy,
        EventServiceStrategy $eventStrategy,
        RestaurantServiceStrategy $restaurantStrategy,
        CateringServiceStrategy $cateringStrategy
    ) {
        $this->strategies = [
            'property' => $propertyStrategy,
            'event' => $eventStrategy,
            'restaurant' => $restaurantStrategy,
            'catering' => $cateringStrategy,
        ];
    }
    
    public function createService(array $data): Service
    {
        Log::info("ServiceFactory::createService - data received", [
            'data_keys' => array_keys($data),
            'has_catering' => isset($data['catering']),
            'has_catering_name' => isset($data['catering_name']),
            'has_catering_items' => isset($data['catering_items']),
            'catering_value' => $data['catering'] ?? 'NOT_SET'
        ]);
        
        $serviceType = $this->detectServiceType($data);
        
        if (!isset($this->strategies[$serviceType])) {
            throw new \InvalidArgumentException("Unsupported service type: {$serviceType}");
        }
        
        $strategy = $this->strategies[$serviceType];
        
        // Skip custom validation - Laravel's built-in validation from FormRequest already handled this
        // Log for debugging but don't validate again
        Log::info("Using Laravel built-in validation", [
            'service_type' => $serviceType,
            'data_keys' => array_keys($data)
        ]);
        
        Log::info("Creating service using strategy", [
            'service_type' => $serviceType,
            'user_id' => auth()->id()
        ]);
        
        return $strategy->createService($data);
    }
    
    public function detectServiceType(array $data): string
    {
        // تحديد نوع الخدمة بناءً على البيانات المرسلة
        if (isset($data['property'])) {
            return 'property';
        }
        
        if (isset($data['event'])) {
            return 'event';
        }
        
        if (isset($data['restaurant'])) {
            return 'restaurant';
        }
        
        if (isset($data['catering'])) {
            return 'catering';
        }
        
        // Check for flat catering fields (admin requests)
        if (isset($data['catering_name']) || isset($data['cuisine_type']) || isset($data['min_order_amount'])) {
            return 'catering';
        }
        
        // إذا كان هناك catering_item فقط بدون catering رئيسي
        if (isset($data['catering_item'])) {
            throw new \InvalidArgumentException(
                'Send catering main payload under "catering" and add-ons under "catering_item.packages". ' .
                'Creating a standalone catering_item without catering is not allowed.'
            );
        }
        
        throw new \InvalidArgumentException(
            'A valid service type (e.g., property, event, restaurant, catering) is required.'
        );
    }
    
    /**
     * الحصول على الاستراتيجية المطلوبة لنوع خدمة معين
     *
     * @param string $serviceType
     * @return IServiceCreationStrategy
     */
    public function getStrategy(string $serviceType): IServiceCreationStrategy
    {
        if (!isset($this->strategies[$serviceType])) {
            throw new \InvalidArgumentException("Unsupported service type: {$serviceType}");
        }
        
        return $this->strategies[$serviceType];
    }
    
    /**
     * الحصول على جميع أنواع الخدمات المدعومة
     *
     * @return array
     */
    public function getSupportedServiceTypes(): array
    {
        return array_keys($this->strategies);
    }
}
