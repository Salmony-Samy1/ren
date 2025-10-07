<?php

namespace Database\Seeders;

use App\Enums\CompanyLegalDocType;
use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\CompanyLegalDocument;
use App\Models\CompanyProfile;
use App\Models\Currency;
use App\Models\MainService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProviderOnboardingScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Provider registers
            $provider = User::firstOrCreate(
                ['email' => 'provider.onboarding@example.com'],
                [
                    'full_name' => 'مزود تجريبي',
                    'password' => bcrypt('123456789'),
                    'phone' => '0555555555',
                    'country_code' => 'SA',
                    'type' => 'provider',
                ]
            );

            // Simulate OTP verification (settings have otp_gateway=static)
            $provider->phone_verified_at = now();
            $provider->email_verified_at = now();
            $provider->save();

            // Create company profile if missing
            $cityId = City::query()->whereHas('translations', fn($q)=>$q->where('name','الرياض'))->value('id')
                ?? City::query()->value('id');
            $company = CompanyProfile::firstOrCreate(
                ['user_id' => $provider->id],
                [
                    'name' => 'شركة الرؤية الحديثة',
                    'owner' => 'م. مازن',
                    'national_id' => '7000000001',
                    'company_name' => 'Modern Vision Co',
                    'country_id' => 1, // SA
                    'city_id' => $cityId,
                    'company_logo_url' => null,
                ]
            );

            // 2) Submit legal documents per main service
            $mainServices = MainService::pluck('id','name_en');
            $docsMap = [
                'Events' => [CompanyLegalDocType::TOURISM_LICENSE->value],
                'Catering' => [CompanyLegalDocType::FOOD_SAFETY_CERT->value, CompanyLegalDocType::CATERING_PERMIT->value],
                'Stays (Apartments & Chalets)' => [CompanyLegalDocType::COMMERCIAL_REGISTRATION->value],
            ];

            foreach ($docsMap as $msName => $docTypes) {
                $msId = $mainServices[$msName] ?? null;
                if (!$msId) { continue; }
                foreach ($docTypes as $type) {
                    CompanyLegalDocument::create([
                        'company_profile_id' => $company->id,
                        'main_service_id' => $msId,
                        'doc_type' => $type,
                        'file_path' => 'legal_docs/'.$company->id.'/'.$type.'.pdf',
                        'status' => ReviewStatus::APPROVED->value,
                        'approved_at' => now(),
                        'review_notes' => 'Auto-approved for demo',
                    ]);
                }
            }

            // 3) Create diverse services per main service with categories and realistic data
            // Choose currencies
            $sar = Currency::where('code','SAR')->first();
            $bhd = Currency::where('code','BHD')->first();

            // Helper to create service
            $createService = function(array $svc, array $childData, callable $childCreator) use ($provider) {
                $service = Service::create(array_merge([
                    'user_id' => $provider->id,
                    'is_approved' => true,
                    'approved_at' => now(),
                    'price_currency' => $svc['currency'] ?? 'SAR',
                    'price_amount' => $svc['price_amount'] ?? null,
                    'name' => $svc['name'],
                    'category_id' => $svc['category_id'],
                    'city_id' => $svc['city_id'] ?? null,
                    'address' => $svc['address'] ?? 'الرياض، المملكة العربية السعودية',
                    'latitude' => $svc['latitude'] ?? 24.7136,
                    'longitude' => $svc['longitude'] ?? 46.6753,
                    'available_from' => now()->toDateString(),
                    'available_to' => now()->addMonths(3)->toDateString(),
                ]));

                // child record
                $childCreator($service, $childData);

                // Attach one image placeholder path (media table usually requires real files; keep URL/meta in model if needed)
                try { $service->addMediaFromUrl('https://picsum.photos/seed/'.uniqid().'/800/500')->toMediaCollection('images'); } catch (\Throwable $e) {}
                return $service;
            };

            // EVENTS: 5-6 categories * 5-6 examples each
            $eventCats = Category::whereHas('mainService', fn($q)=>$q->where('name_en','Events'))->pluck('id','id');
            foreach ($eventCats as $catId) {
                for ($i=1; $i<=5; $i++) {
                    $evName = ['يوغا الغروب','ماراثون ترفيهي','أمسية شعرية','ورشة تصوير','مهرجان أطعمة'][($i-1)%5];
                    $createService([
                        'name' => $evName.' - '.$i,
                        'category_id' => $catId,
                        'price_amount' => rand(30,120),
                        'currency' => 'SAR',
                        'city_id' => $cityId,
                    ], [
                        'event_name' => $evName,
                        'description' => 'فعالية '.$evName.' مناسبة للعائلات والشباب.',
                        'max_individuals' => rand(30,150),
                        'gender_type' => 'both',
                        'hospitality_available' => (bool)rand(0,1),
                        'pricing_type' => 'fixed',
                        'base_price' => rand(30,120),
                        'discount_price' => null,
                        'prices_by_age' => [],
                        'cancellation_policy' => 'flexible',
                        'meeting_point' => 'موقع الفعالية يرسل بعد الحجز',
                        'start_at' => now()->addDays(rand(1,50))->setTime(rand(9,19),0),
                        'end_at' => now()->addDays(rand(1,50))->setTime(rand(20,23),0),
                    ], function(Service $service, array $data) {
                        $service->event()->create($data);
                    });
                }
            }

            // CATERING: 5-6 categories * 5-6 examples each
            $catCats = Category::whereHas('mainService', fn($q)=>$q->where('name_en','Catering'))->pluck('id','id');
            foreach ($catCats as $catId) {
                for ($i=1; $i<=5; $i++) {
                    $meal = ['مندي لحم','مشاوي مشكلة','مأكولات بحرية','إيطالي عائلي','بوكس إفطار'][($i-1)%5];
                    $createService([
                        'name' => $meal.' - '.$i,
                        'category_id' => $catId,
                        'price_amount' => rand(150,600),
                        'currency' => 'SAR',
                        'city_id' => $cityId,
                    ], [
                        'description' => ['ar' => 'باقة '.$meal.' تكفي من 5 إلى 10 أشخاص.'],
                        'available_stock' => rand(5,40),
                        'fulfillment_methods' => ['DELIVERY','PICKUP'],
                    ], function(Service $service, array $data) {
                        $service->catering()->create($data);
                    });
                }
            }

            // STAYS: 5-6 categories * 5-6 examples each (شاليهات/شقق/فلل...)
            $stayCats = Category::whereHas('mainService', fn($q)=>$q->where('name_en','Stays (Apartments & Chalets)'))->pluck('id','id');
            foreach ($stayCats as $catId) {
                for ($i=1; $i<=5; $i++) {
                    $unit = ['شاليه ساحلي','شقة فاخرة','فيلا حديثة','مزرعة ريفية','استوديو عملي'][($i-1)%5];
                    $createService([
                        'name' => $unit.' - '.$i,
                        'category_id' => $catId,
                        'price_amount' => rand(300,1500),
                        'currency' => 'SAR',
                        'city_id' => $cityId,
                    ], [
                        'property_name' => $unit,
                        'type' => 'chalet',
                        'category' => 'property',
                        'unit_code' => 'UNIT-'.uniqid(),
                        'area_sqm' => rand(80,400),
                        'down_payment_percentage' => 0,
                        'is_refundable_insurance' => false,
                        'cancellation_policy' => 'flexible',
                        'description' => 'وحدة '.$unit.' مناسبة للعائلات وبإطلالة مميزة.',
                        'allowed_category' => 'family',
                        'room_details' => [ ['name' => 'Bedroom', 'beds' => rand(1,3), 'bathroom_inside' => (bool)rand(0,1)] ],
                        'access_instructions' => 'سيتم إرسال كود الدخول بعد التأكيد',
                        'nightly_price' => rand(300,1500),
                        'max_adults' => rand(2,8),
                        'max_children' => rand(0,4),
                        'checkin_time' => '15:00',
                        'checkout_time' => '12:00',
                    ], function(Service $service, array $data) {
                        $prop = $service->property()->create($data);
                        // Create nested structured relations so they appear in payload
                        $prop->bedrooms()->createMany([
                            ['beds_count' => rand(1,2), 'is_master' => true],
                            ['beds_count' => rand(1,3), 'is_master' => false],
                        ]);
                        $prop->kitchens()->createMany([
                            ['appliances' => ['fridge','microwave'], 'dining_chairs' => rand(2,6)],
                        ]);
                        $prop->bathrooms()->createMany([
                            ['amenities' => ['type' => 'full', 'count' => rand(1,2), 'features' => 'shower']],
                        ]);
                        $prop->pools()->createMany([
                            ['type' => 'outdoor', 'length_m' => rand(6,12), 'width_m' => rand(3,5), 'depth_m' => 1.6, 'has_heating' => (bool)rand(0,1), 'has_barrier' => false, 'has_water_games' => false],
                        ]);
                        $prop->livingRooms()->createMany([
                            ['type' => 'main hall'],
                        ]);
                    });
                }
            }
        });
    }
}

