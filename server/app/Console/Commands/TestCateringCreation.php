<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Country;
use App\Services\CateringService;
use App\Services\ServiceCreation\ServiceFactory;

class TestCateringCreation extends Command
{
    protected $signature = 'catering:test-creation';
    protected $description = 'Test catering service creation with sample data';

    public function handle()
    {
        $this->info('Testing Catering Service Creation...');
        
        // Test 1: Check required data exists
        $this->info('1. Checking required data exists...');
        
        $provider = User::where('id', 3)->where('type', 'provider')->first();
        if (!$provider) {
            $this->error('❌ Provider with ID 3 not found or not a provider!');
            return 1;
        }
        $this->info("✅ Provider found: {$provider->name} ({$provider->email})");
        
        $category = Category::find(2);
        if (!$category) {
            $this->error('❌ Category with ID 2 not found!');
            return 1;
        }
        $this->info("✅ Category found: {$category->name}");
        
        $currency = Currency::find(1);
        if (!$currency) {
            $this->error('❌ Currency with ID 1 not found!');
            return 1;
        }
        $this->info("✅ Currency found: {$currency->name}");
        
        $country = Country::find(1);
        if (!$country) {
            $this->error('❌ Country with ID 1 not found!');
            return 1;
        }
        $this->info("✅ Country found: {$country->name}");
        
        // Test 2: Prepare test data
        $this->info('2. Preparing test data...');
        
        $testData = [
            'provider_id' => 3,
            'category_id' => 2,
            'name' => 'بوفيه اختبار',
            'description' => 'بوفيه للاختبار',
            'address' => 'الرياض، المملكة العربية السعودية',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'place_id' => 'ChIJd8BlQ2BZwokRAFUEcm_qrcA',
            'price_currency_id' => 1,
            'price_amount' => 100.00,
            'gender_type' => 'both',
            'country_id' => 1,
            
            'catering_name' => 'بوفيه اختبار',
            'cuisine_type' => 'عربي',
            'min_order_amount' => 1500.00,
            'max_order_amount' => 5000.00,
            'preparation_time' => 120,
            'delivery_available' => true,
            'delivery_radius_km' => 50,
            'available_stock' => 50,
            'fulfillment_methods' => ['delivery', 'pickup'],
            
            'catering_item' => [
                'packages' => [
                    [
                        'package_name' => 'بوفيه أساسي',
                        'price' => 1500.00,
                        'available_stock' => 25,
                        'items' => ['أرز بخاري', 'دجاج مشوي', 'سلطة خضراء', 'مشروبات']
                    ]
                ]
            ]
        ];
        
        // Test 3: Try to create service using ServiceFactory
        $this->info('3. Testing service creation...');
        
        try {
            // Set auth context
            auth()->login($provider);
            
            $serviceFactory = new ServiceFactory(
                app(\App\Services\ServiceCreation\Strategies\PropertyServiceStrategy::class),
                app(\App\Services\ServiceCreation\Strategies\EventServiceStrategy::class),
                app(\App\Services\ServiceCreation\Strategies\RestaurantServiceStrategy::class),
                app(\App\Services\ServiceCreation\Strategies\CateringServiceStrategy::class)
            );
            
            $service = $serviceFactory->createService($testData);
            
            if ($service && $service->id) {
                $this->info("✅ Service created successfully with ID: {$service->id}");
                $this->info("✅ Service name: {$service->name}");
                
                // Check if catering was created
                if ($service->catering) {
                    $this->info("✅ Catering record created with ID: {$service->catering->id}");
                    $this->info("✅ Catering name: {$service->catering->catering_name}");
                    
                    // Check catering items
                    $cateringItemsCount = $service->catering->items()->count();
                    $this->info("✅ Catering items created: {$cateringItemsCount}");
                    
                    // Clean up test data
                    $service->delete();
                    $this->info("✅ Test service deleted successfully");
                } else {
                    $this->error("❌ Catering record not created");
                    return 1;
                }
            } else {
                $this->error("❌ Service creation failed - returned null or no ID");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Service creation failed with exception: {$e->getMessage()}");
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        $this->info('🎉 All catering creation tests passed!');
        return 0;
    }
}

