<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\MainService;
use App\Models\Category;
use App\Models\Service;
use App\Models\Property;
use App\Models\Restaurant;
use App\Models\CateringItem;

class TestMinimalSeeder extends Seeder
{
    public function run(): void
    {
        // Main services (ensure name_en to satisfy NOT NULL)
        $msEvent = MainService::firstOrCreate(['name' => 'ترفيه وفعاليات'], ['name_en' => 'Entertainment & Events']);
        $msCatering = MainService::firstOrCreate(['name' => 'كيترينج'], ['name_en' => 'Catering']);
        $msRestaurant = MainService::firstOrCreate(['name' => 'مطاعم وحجز طاولات'], ['name_en' => 'Restaurants & Tables']);
        $msProperty = MainService::firstOrCreate(['name' => 'شقق وشاليهات'], ['name_en' => 'Stays']);

        // Categories with translations and icons
        $catEvent = Category::firstOrCreate(['main_service_id' => $msEvent->id, 'status' => true], ['icon' => '/images/cat_event.png']);
        $catEvent->translateOrNew('ar')->name = 'فعاليات عامة';
        $catEvent->translateOrNew('ar')->description = 'فعاليات ترفيهية متنوعة';
        $catEvent->translateOrNew('en')->name = 'Events';
        $catEvent->translateOrNew('en')->description = 'Entertainment events';
        $catEvent->save();

        $catCatering = Category::firstOrCreate(['main_service_id' => $msCatering->id, 'status' => true], ['icon' => '/images/cat_catering.png']);
        $catCatering->translateOrNew('ar')->name = 'كيترينج';
        $catCatering->translateOrNew('ar')->description = 'خدمات الضيافة والبوفيه';
        $catCatering->translateOrNew('en')->name = 'Catering';
        $catCatering->translateOrNew('en')->description = 'Catering services';
        $catCatering->save();

        $catRestaurant = Category::firstOrCreate(['main_service_id' => $msRestaurant->id, 'status' => true], ['icon' => '/images/cat_restaurant.png']);
        $catRestaurant->translateOrNew('ar')->name = 'مطاعم';
        $catRestaurant->translateOrNew('ar')->description = 'مطاعم وحجز طاولات';
        $catRestaurant->translateOrNew('en')->name = 'Restaurants';
        $catRestaurant->translateOrNew('en')->description = 'Restaurants & table booking';
        $catRestaurant->save();

        $catProperty = Category::firstOrCreate(['main_service_id' => $msProperty->id, 'status' => true], ['icon' => '/images/cat_property.png']);
        $catProperty->translateOrNew('ar')->name = 'إقامات';
        $catProperty->translateOrNew('ar')->description = 'شقق وشاليهات واستوديوهات';
        $catProperty->translateOrNew('en')->name = 'Stays';
        $catProperty->translateOrNew('en')->description = 'Apartments, chalets, studios';
        $catProperty->save();

        // Users
        $provider = User::firstOrCreate(
            ['email' => 'prov@example.com'],
            [
                'full_name' => 'مزود عام',
                'password' => Hash::make('P@ssw0rd!'),
                'phone' => '+966500000001',
                'country_code' => '+966',
                'type' => 'provider',
                'is_approved' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'uuid' => (string) Str::uuid(),
            ]
        );
        $customer = User::firstOrCreate(
            ['email' => 'cust@example.com'],
            [
                'full_name' => 'عميل عام',
                'password' => Hash::make('P@ssw0rd!'),
                'phone' => '+966500000002',
                'country_code' => '+966',
                'type' => 'customer',
                'is_approved' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'uuid' => (string) Str::uuid(),
            ]
        );
        $customer2 = User::firstOrCreate(
            ['email' => 'cust2@example.com'],
            [
                'full_name' => 'عميل 2',
                'password' => Hash::make('P@ssw0rd!'),
                'phone' => '+966500000003',
                'country_code' => '+966',
                'type' => 'customer',
                'is_approved' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'uuid' => (string) Str::uuid(),
            ]
        );

        // Services + typed details (including Event)
        $svcEvent = Service::firstOrCreate([
            'user_id' => $provider->id,
            'category_id' => $catEvent->id,
            'name' => 'يوجا الصباح',
        ], [
            'is_approved' => true,
            'price_amount' => 50,
            'rating_avg' => 4.1,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
        \App\Models\Event::firstOrCreate(['service_id' => $svcEvent->id], [
            'event_name' => 'جلسة يوجا صباحية',
            'description' => 'جلسة يوجا للاسترخاء واللياقة',
            'images' => '',
            'max_individuals' => 20,
            'gender_type' => 'both',
            'hospitality_available' => true,
            'pricing_type' => 'fixed',
            'base_price' => 50,
            'discount_price' => null,
            'start_at' => now()->addDays(1)->setTime(9,0),
            'end_at' => now()->addDays(1)->setTime(11,0),
            'prices_by_age' => [],
            'cancellation_policy' => 'flexible',
            'meeting_point' => 'الحديقة العامة'
        ]);

        $svcProperty = Service::firstOrCreate([
            'user_id' => $provider->id,
            'category_id' => $catProperty->id,
            'name' => 'شاليه عائلي',
        ], [
            'is_approved' => true,
            'price_amount' => 800,
            'rating_avg' => 4.5,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
        Property::firstOrCreate(['service_id' => $svcProperty->id], [
            'property_name' => 'وحدة شاليه A',
            'type' => 'chalet',
            'category' => 'family',
            'images' => [],
            'unit_code' => (string) Str::uuid(),
            'area_sqm' => 120,
            'down_payment_percentage' => 10,
            'is_refundable_insurance' => true,
            'cancellation_policy' => 'flexible',
            'description' => 'شاليه بإطلالة جميلة',
            'allowed_category' => 'family',
            'room_details' => [],
            // 'facilities' removed by migration 2025_09_08_003324_remove_facilities_column_from_properties_table
            'access_instructions' => 'بوابة 3، كود الباب 1234',
            'checkin_time' => '15:00',
            'checkout_time' => '11:00',
            'nightly_price' => 500,
            'max_adults' => 6,
            'max_children' => 3,
        ]);

        $svcRestaurant = Service::firstOrCreate([
            'user_id' => $provider->id,
            'category_id' => $catRestaurant->id,
            'name' => 'مطعم الأصيل',
        ], [
            'is_approved' => true,
            'price_amount' => 150,
            'rating_avg' => 4.7,
            'latitude' => 24.7,
            'longitude' => 46.6,
        ]);
        Restaurant::firstOrCreate(['service_id' => $svcRestaurant->id], [
            'images' => '',
            'daily_available_bookings' => 100,
            'total_tables' => 20,
            'description' => 'مطعم عربي مميز',
            'working_hours' => ['sat-thu' => ['12:00','23:00'], 'fri' => ['16:00','23:59']],
            'available_tables_map' => null,
        ]);

        $svcCatering = Service::firstOrCreate([
            'user_id' => $provider->id,
            'category_id' => $catCatering->id,
            'name' => 'بوفيه عائلي',
        ], [
            'is_approved' => true,
            'price_amount' => 300,
            'rating_avg' => 4.2,
            'latitude' => 24.72,
            'longitude' => 46.67,
        ]);
        CateringItem::firstOrCreate(['service_id' => $svcCatering->id], [
            'meal_name' => 'بوفيه عائلي أساسي',
            'price' => 300,
            'servings_count' => 6,
            'availability_schedule' => 'daily',
            'delivery_included' => true,
            'offer_duration' => now()->addDays(7),
            'available_stock' => 50,
            'description' => 'كيترينج متنوع يناسب العوائل',
            'packages' => [],
        ]);

        // Seed gift packages

        \App\Models\GiftPackage::firstOrCreate(['name' => 'هدية 50'], [
            'amount' => 50, 'image_url' => null, 'active' => true, 'description' => 'باقة هدية 50 ريال', 'sort_order' => 1,
        ]);
        \App\Models\GiftPackage::firstOrCreate(['name' => 'هدية 100'], [
            'amount' => 100, 'image_url' => null, 'active' => true, 'description' => 'باقة هدية 100 ريال', 'sort_order' => 2,
        ]);

        // Fund wallets for testing transfers and gifts
        // Assign wallet currencies (default SAR) and fund
        if (!$provider->wallet->currency) { $provider->wallet->currency = 'SAR'; $provider->wallet->save(); }
        if (!$customer->wallet->currency) { $customer->wallet->currency = 'SAR'; $customer->wallet->save(); }
        if (!$customer2->wallet->currency) { $customer2->wallet->currency = 'SAR'; $customer2->wallet->save(); }

        if ($provider->balance < 1000) { $provider->deposit(1000, ['currency' => 'SAR']); }
        if ($customer->balance < 500) { $customer->deposit(500, ['currency' => 'SAR']); }
        if ($customer2->balance < 300) { $customer2->deposit(300, ['currency' => 'SAR']); }
    }
}


