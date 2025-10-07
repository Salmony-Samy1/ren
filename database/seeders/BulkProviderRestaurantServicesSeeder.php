<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BulkProviderRestaurantServicesSeeder extends Seeder
{
    public function run(): void
    {
        $providerId = 2; // requested provider user_id
        $provider = User::find($providerId);
        if (!$provider) {
            $this->command->warn("Provider user #{$providerId} not found. Aborting.");
            return;
        }

        $today = now()->startOfDay();
        $tomorrow = now()->copy()->addDay()->startOfDay();
        $afterTomorrow = now()->copy()->addDays(2)->startOfDay();
        $endOfWeek = now()->endOfWeek();

        // Category IDs 13..18 inclusive
        $categoryIds = range(13, 18);

        // Locate SAR currency id if exists
        $sarCurrencyId = DB::table('currencies')->where('code', 'SAR')->value('id');

        // Pick a city id if available
        $cityId = DB::table('cities')->value('id');

        // Sample addresses and geos
        $addresses = [
            'الرياض، حي العليا، طريق الملك فهد',
            'الرياض، حي الياسمين، طريق أنس بن مالك',
            'الرياض، حي الملك عبدالله، طريق تركي الأول',
            'الرياض، حي العقيق، طريق الإمام سعود بن فيصل',
            'الرياض، حي النخيل، الدائري الشمالي',
            'الرياض، حي الملز، شارع الأحساء',
        ];
        $geos = [
            ['lat' => 24.7136, 'lng' => 46.6753, 'place_id' => 'ChIJ6zMe3o5LjhURb8sP5Z6rZ9Y'],
            ['lat' => 24.7743, 'lng' => 46.7386, 'place_id' => 'ChIJ5xJq2qlLjhUR1q5R2mZf2nE'],
            ['lat' => 24.7458, 'lng' => 46.6345, 'place_id' => 'ChIJq6qqq6lLjhURpQh3pKXk1bM'],
            ['lat' => 24.7997, 'lng' => 46.6872, 'place_id' => 'ChIJkXN9b6lLjhUR3g7YH3f6F34'],
            ['lat' => 24.7580, 'lng' => 46.6580, 'place_id' => 'ChIJd8Q2cqlLjhURc4TQWk2l9h8'],
            ['lat' => 24.6900, 'lng' => 46.7200, 'place_id' => 'ChIJf8G2gqlLjhUR2h2Z2k3l1v9'],
        ];
        $districts = ['العليا','الياسمين','الملك عبدالله','العقيق','النخيل','الملز'];

        // Sample image URLs
        $imagePool = [
            'https://images.unsplash.com/photo-1528605248644-14dd04022da1?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1541542684-4a6313e31f50?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1552566626-52f8b828add9?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1551218808-94e220e084d2?q=80&w=1200&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1533777419517-7c60f6f1fcd1?q=80&w=1200&auto=format&fit=crop',
        ];

        $updated = 0;

        DB::beginTransaction();
        try {
            for ($i = 1; $i <= 40; $i++) {
                $categoryId = $categoryIds[($i - 1) % count($categoryIds)];
                $name = "مطعم وطاولات - تجربة #$i";

                $idx = ($i - 1) % count($addresses);
                $addr = $addresses[$idx];
                $geo = $geos[$idx];
                $district = $districts[$idx];

                $service = Service::updateOrCreate(
                    [ 'user_id' => $provider->id, 'name' => $name ],
                    [
                        'category_id' => $categoryId,
                        'is_approved' => true,
                        'approved_at' => now(),
                        'approval_notes' => 'Bulk seeding: approved for demo readiness.',
                        'price_currency' => 'SAR',
                        'price_currency_id' => $sarCurrencyId,
                        'price_amount' => rand(60, 250),
                        'available_from' => $today->toDateString(),
                        'available_to' => $endOfWeek->toDateString(),
                        'address' => $addr,
                        'latitude' => $geo['lat'],
                        'longitude' => $geo['lng'],
                        'place_id' => $geo['place_id'],
                        'city_id' => $cityId,
                        'district' => $district,
                        'gender_type' => 'both',
                    ]
                );

                // Attach 2 images per service if none
                try {
                    if (method_exists($service, 'getMedia') && $service->getMedia('images')->count() === 0) {
                        $imgUrl1 = $imagePool[($i - 1) % count($imagePool)];
                        $imgUrl2 = $imagePool[($i) % count($imagePool)];
                        $service->addMediaFromUrl($imgUrl1)->toMediaCollection('images');
                        $service->addMediaFromUrl($imgUrl2)->toMediaCollection('images');
                    }
                } catch (\Throwable $e) {
                    Log::warning("Image attach failed for service #{$service->id}: " . $e->getMessage());
                }

                // Create or update restaurant profile
                $restaurant = $service->restaurant;
                if (!$restaurant) {
                    $restaurant = $service->restaurant()->create([
                        'images' => '',
                        'daily_available_bookings' => 100,
                        'total_tables' => 20,
                        'description' => 'خدمة حجز طاولات بمستويات مختلفة وجودة عالية ضمن ' . $district,
                        'working_hours' => [
                            'mon-thu' => ['12:00','23:00'],
                            'fri' => ['16:00','23:59'],
                            'sat' => ['12:00','23:59'],
                            'sun' => ['12:00','23:00'],
                        ],
                        'available_tables_map' => null,
                    ]);
                } else {
                    $restaurant->update([
                        'daily_available_bookings' => 120,
                        'total_tables' => 24,
                        'description' => 'خدمة حجز طاولات محدثة بجودة عالية ضمن ' . $district,
                    ]);
                }

                // Create tables if none exist
                if ($restaurant->tables()->count() === 0) {
                    $restaurant->tables()->createMany([
                        [
                            'name' => 'طاولة عائلية',
                            'type' => 'Normal',
                            'capacity_people' => 4,
                            'price_per_person' => 65,
                            'price_per_table' => null,
                            'quantity' => 5,
                            're_availability_type' => 'AUTO',
                            'auto_re_availability_minutes' => 15,
                        ],
                        [
                            'name' => 'طاولة VIP',
                            'type' => 'VIP',
                            'capacity_people' => 6,
                            'price_per_person' => null,
                            'price_per_table' => 520,
                            'quantity' => 2,
                            're_availability_type' => 'AUTO',
                            'auto_re_availability_minutes' => 30,
                        ],
                    ]);
                }

                $updated++;
            }

            DB::commit();
            $this->command->info("Upserted {$updated} restaurant/table services for provider #{$provider->id} with full fields.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Failed seeding restaurant services: ' . $e->getMessage());
            throw $e;
        }
    }
}

