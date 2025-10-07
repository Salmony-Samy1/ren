<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Property;
use App\Models\Category;
use App\Models\MainService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have a local image to attach; fallback to placeholder
        $placeholder = database_path('seeders/assets/sample.jpg');
        if (!file_exists($placeholder)) {
            @mkdir(database_path('seeders/assets'), 0777, true);
            // create a 1x1 png
            $img = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');
            file_put_contents($placeholder, $img);
        }

        // Use existing Stays (Apartments & Chalets) main service categories (id=4)
        $staysMain = MainService::where('name_en','Stays (Apartments & Chalets)')->first();
        $categoryIds = Category::where('main_service_id', optional($staysMain)->id ?? 4)->pluck('id')->all();
        if (empty($categoryIds)) {
            // fallback: any categories
            $categoryIds = Category::pluck('id')->all();
        }

        $provider = User::where('type','provider')->first();
        if (!$provider) { $provider = User::first(); }

        // Pick a city to attach (prefer Riyadh, else first city)
        $cityId = \App\Models\City::whereHas('translations', function($q){ $q->where('name','الرياض'); })->value('id');
        if (!$cityId) { $cityId = \App\Models\City::first()?->id; }

        for ($i = 1; $i <= 20; $i++) {
            $cat = $categoryIds[array_rand($categoryIds)];

            $service = Service::create([
                'name' => 'Demo Chalet #'.$i,
                'category_id' => $cat,
                'user_id' => $provider?->id ?? 1,
                'address' => 'Riyadh, District '.($i%10+1),
                'latitude' => 24.7 + mt_rand(0, 1000)/10000,
                'longitude' => 46.6 + mt_rand(0, 1000)/10000,
                'place_id' => (string) Str::uuid(),
                'city_id' => $cityId,
                'price_currency_id' => 1,
                'price_amount' => rand(500, 2500),
                'is_approved' => true,
                'approved_at' => now(),
            ]);

            $prop = $service->property()->create([
                'property_name' => 'Unit '.$i,
                'type' => 'chalet',
                'category' => 'property',
                'unit_code' => 'UNIT-'.str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'area_sqm' => rand(100, 400),
                'down_payment_percentage' => 0,
                'is_refundable_insurance' => false,
                'cancellation_policy' => 'flexible',
                'description' => 'Demo auto-generated property #'.$i,
                'allowed_category' => 'family',
                'room_details' => [ ['name' => 'Bedroom', 'beds' => 2, 'bathroom_inside' => false] ],
                'access_instructions' => 'door code 1234',
                'nightly_price' => rand(500, 2500),
                'max_adults' => rand(2, 8),
                'max_children' => rand(0, 4),
                'checkin_time' => '15:00',
                'checkout_time' => '12:00',
                'city_id' => $cityId,
            ]);

            // Nested relations
            $prop->bedrooms()->createMany([
                ['beds_count' => 1, 'is_master' => true],
                ['beds_count' => 2, 'is_master' => false],
            ]);
            $prop->kitchens()->createMany([
                ['appliances' => ['fridge','microwave'], 'dining_chairs' => 4],
            ]);
            $prop->bathrooms()->createMany([
                ['amenities' => ['type' => 'full', 'count' => 2, 'features' => 'shower']],
            ]);
            $prop->pools()->createMany([
                ['type' => 'outdoor', 'length_m' => 8, 'width_m' => 3.5, 'depth_m' => 1.6, 'has_heating' => false, 'has_barrier' => false, 'has_water_games' => false],
            ]);
            $prop->livingRooms()->createMany([
                ['type' => 'main hall'],
            ]);

            // Attach media to service and property
            try {
                $service->addMedia($placeholder)->preservingOriginal()->toMediaCollection('images');
            } catch (\Throwable $e) { /* ignore */ }
            try {
                $prop->addMedia($placeholder)->preservingOriginal()->toMediaCollection('property_images');
            } catch (\Throwable $e) { /* ignore */ }
        }
    }
}

