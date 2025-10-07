<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\MainService;
use App\Models\Service;
use App\Models\Activity;
use App\Models\CateringItem;
use App\Models\Restaurant;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Review;
use App\Models\Follow;
use App\Models\Wish;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CustomerProfile;
use App\Models\CompanyProfile;
use App\Models\City;
use App\Models\Region;
use App\Models\Neigbourhood;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GathroDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // إنشاء الخدمات الرئيسية
            $mainServices = [
                ['name' => 'ترفيه وفعاليات'],
                ['name' => 'كيترينج'],
                ['name' => 'مطاعم وحجز طاولات'],
                ['name' => 'شقق وشاليهات'],
            ];
            
            $mainServiceIds = [];
            foreach ($mainServices as $service) {
                $mainService = MainService::create($service);
                $mainServiceIds[] = $mainService->id;
            }
            
            // إنشاء الفئات
            $categories = [
                ['name' => 'حفلات عيد الميلاد', 'main_service_id' => $mainServiceIds[0], 'status' => true],
                ['name' => 'حفلات الزفاف', 'main_service_id' => $mainServiceIds[0], 'status' => true],
                ['name' => 'مناسبات خاصة', 'main_service_id' => $mainServiceIds[0], 'status' => true],
                ['name' => 'وجبات عائلية', 'main_service_id' => $mainServiceIds[1], 'status' => true],
                ['name' => 'وجبات شركات', 'main_service_id' => $mainServiceIds[1], 'status' => true],
                ['name' => 'مطاعم عربية', 'main_service_id' => $mainServiceIds[2], 'status' => true],
                ['name' => 'مطاعم أجنبية', 'main_service_id' => $mainServiceIds[2], 'status' => true],
                ['name' => 'شقق فاخرة', 'main_service_id' => $mainServiceIds[3], 'status' => true],
                ['name' => 'شاليهات عائلية', 'main_service_id' => $mainServiceIds[3], 'status' => true],
            ];
            
            foreach ($categories as $category) {
                Category::create($category);
            }
            
            // إنشاء المشرف
            $admin = User::create([
                'full_name' => 'أحمد المشرف',
                'email' => 'admin@gathro.com',
                'password' => Hash::make('12345678'),
                'phone' => '+966501234567',
                'type' => 'admin',
                'is_approved' => true,
                'email_verified_at' => now(),
                'uuid' => Str::uuid(),
                'country_code' => '+966',
            ]);
            
            // إنشاء مقدمي الخدمات
            $providers = [
                [
                    'full_name' => 'محمد الترفيهي',
                    'email' => 'mohamed.entertainment@gathro.com',
                    'phone' => '+966501234568',
                    'type' => 'provider',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'company_name' => 'شركة الترفيه المميزة',
                    'commercial_record' => 'CR123456789',
                    'tax_number' => 'TX987654321',
                    'main_service_id' => $mainServiceIds[0],
                ],
                [
                    'full_name' => 'فاطمة الكيترينج',
                    'email' => 'fatima.catering@gathro.com',
                    'phone' => '+966501234569',
                    'type' => 'provider',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'company_name' => 'مطبخ فاطمة للكيترينج',
                    'commercial_record' => 'CR987654321',
                    'tax_number' => 'TX123456789',
                    'main_service_id' => $mainServiceIds[1],
                ],
                [
                    'full_name' => 'علي المطعم',
                    'email' => 'ali.restaurant@gathro.com',
                    'phone' => '+966501234570',
                    'type' => 'provider',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'company_name' => 'مطعم علي الأصيل',
                    'commercial_record' => 'CR456789123',
                    'tax_number' => 'TX654321987',
                    'main_service_id' => $mainServiceIds[2],
                ],
                [
                    'full_name' => 'سارة العقارية',
                    'email' => 'sara.property@gathro.com',
                    'phone' => '+966501234571',
                    'type' => 'provider',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'company_name' => 'شركة سارة للعقارات',
                    'commercial_record' => 'CR789123456',
                    'tax_number' => 'TX321987654',
                    'main_service_id' => $mainServiceIds[3],
                ],
            ];
            
            foreach ($providers as $providerData) {
                $provider = User::create([
                    'full_name' => $providerData['full_name'],
                    'email' => $providerData['email'],
                    'password' => Hash::make('12345678'),
                    'phone' => $providerData['phone'],
                    'type' => $providerData['type'],
                    'is_approved' => $providerData['is_approved'],
                    'email_verified_at' => $providerData['email_verified_at'],
                    'uuid' => Str::uuid(),
                    'country_code' => '+966',
                ]);
                
                CompanyProfile::create([
                    'user_id' => $provider->id,
                    'name' => $providerData['company_name'],
                    'owner' => $providerData['full_name'],
                    'national_id' => 'NID' . rand(100000, 999999),
                    'country_id' => City::first()->country_id,
                    'city_id' => City::first()->id,
                    'company_name' => $providerData['company_name'],
                    'commercial_record' => $providerData['commercial_record'],
                    'tax_number' => $providerData['tax_number'],
                    'description' => 'وصف الشركة هنا',
                    'main_service_id' => $providerData['main_service_id'],
                ]);
            }
            
            // إنشاء المستخدمين
            $customers = [
                [
                    'full_name' => 'أحمد العميل',
                    'first_name' => 'أحمد',
                    'last_name' => 'العميل',
                    'email' => 'ahmed.customer@gathro.com',
                    'phone' => '+966501234572',
                    'type' => 'customer',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'national_id' => '1234567890',
                    'age' => 30,
                    'gender' => 'male',
                ],
                [
                    'full_name' => 'سارة العميلة',
                    'first_name' => 'سارة',
                    'last_name' => 'العميل',
                    'email' => 'sara.customer@gathro.com',
                    'phone' => '+966501234573',
                    'type' => 'customer',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'national_id' => '0987654321',
                    'age' => 25,
                    'gender' => 'female',
                ],
                [
                    'full_name' => 'خالد العميل',
                    'first_name' => 'خالد',
                    'last_name' => 'العميل',
                    'email' => 'khalid.customer@gathro.com',
                    'phone' => '+966501234574',
                    'type' => 'customer',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'national_id' => '1122334455',
                    'age' => 35,
                    'gender' => 'male',
                ],
            ];
            
            foreach ($customers as $customerData) {
                $customer = User::create([
                    'full_name' => $customerData['full_name'],
                    'email' => $customerData['email'],
                    'password' => Hash::make('12345678'),
                    'phone' => $customerData['phone'],
                    'type' => $customerData['type'],
                    'is_approved' => $customerData['is_approved'],
                    'email_verified_at' => $customerData['email_verified_at'],
                    'uuid' => Str::uuid(),
                    'country_code' => '+966',
                ]);
                
                CustomerProfile::create([
                    'user_id' => $customer->id,
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    // 'national_id' => $customerData['national_id'],
                    // 'age' => $customerData['age'],
                    'gender' => $customerData['gender'],
                    // 'city_id' => City::first()->id,
                    'region_id' => Region::first()->id,
                    'neigbourhood_id' => Neigbourhood::first()->id,
                ]);
            }
            
            // إنشاء الخدمات
            $entertainmentProvider = User::where('email', 'mohamed.entertainment@gathro.com')->first();
            $cateringProvider = User::where('email', 'fatima.catering@gathro.com')->first();
            $restaurantProvider = User::where('email', 'ali.restaurant@gathro.com')->first();
            $propertyProvider = User::where('email', 'sara.property@gathro.com')->first();
            
            // خدمة الترفيه

            
            // خدمة الكيترينج
            $catering = CateringItem::create([
                'user_id' => $cateringProvider->id,
                'name' => 'وجبة عائلية شاملة',
                'price' => 200.00,
                'servings_count' => 8,
                'availability_schedule' => 'daily',
                'delivery_included' => true,
                'offer_duration' => 7,
                'available_quantity' => 20,
                'description' => 'وجبة عائلية شاملة مع المشروبات والحلويات',
                'additional_notes' => 'متوفر توصيل مجاني للطلبات فوق 300 ريال',
                'status' => 'active',
            ]);
            
            // خدمة المطعم
            $restaurant = Restaurant::create([
                'user_id' => $restaurantProvider->id,
                'name' => 'مطعم علي الأصيل',
                'description' => 'مطعم يقدم أشهى المأكولات العربية',
                'daily_bookings' => 100,
                'total_tables' => 25,
                'working_hours' => '12:00 - 23:00',
                'location' => 'الرياض، شارع الملك فهد',
                'status' => 'active',
            ]);
            
            // خدمة العقارات
            $property = Property::create([
                'user_id' => $propertyProvider->id,
                'name' => 'شاليه عائلي فاخر',
                'type' => 'chalet',
                'category' => 'luxury',
                'location' => 'الطائف، حي الشفا',
                'unit_code' => 'CH001',
                'area' => 200,
                'down_payment_percentage' => 30,
                'refundable_insurance' => true,
                'cancellation_policy' => 'flexible',
                'description' => 'شاليه فاخر مع إطلالة رائعة على الجبال',
                'allowed_category' => 'families',
                'rooms_count' => 4,
                'beds_count' => 6,
                'bathrooms_count' => 3,
                'kitchen_facilities' => 'مطبخ مجهز بالكامل',
                'pool_facilities' => 'مسبح خاص مع جاكوزي',
                'access_instructions' => 'رقم الطابق 2، صورة المدخل متوفرة',
                'check_in_time' => '15:00',
                'check_out_time' => '11:00',
                'status' => 'active',
            ]);
            
            // إنشاء الحجوزات
            $customers = User::where('type', 'customer')->get();
            
            // حجز خدمة الترفيه
            $entertainmentBooking = Booking::create([
                'user_id' => $customers[0]->id,
                'service_id' => $activity->id,
                'service_type' => 'activity',
                'date' => now()->addDays(7),
                'time' => '18:00',
                'individuals_count' => 20,
                'total_amount' => 2400.00,
                'status' => 'confirmed',
                'additional_details' => 'حفلة عيد ميلاد لابني أحمد',
            ]);
            
            // حجز خدمة الكيترينج
            $cateringBooking = Booking::create([
                'user_id' => $customers[1]->id,
                'service_id' => $catering->id,
                'service_type' => 'catering',
                'date' => now()->addDays(3),
                'time' => '19:00',
                'individuals_count' => 8,
                'total_amount' => 200.00,
                'status' => 'confirmed',
                'additional_details' => 'وجبة عائلية للعشاء',
            ]);
            
            // حجز خدمة المطعم
            $restaurantBooking = Booking::create([
                'user_id' => $customers[2]->id,
                'service_id' => $restaurant->id,
                'service_type' => 'restaurant',
                'date' => now()->addDays(5),
                'time' => '20:00',
                'individuals_count' => 6,
                'total_amount' => 300.00,
                'status' => 'confirmed',
                'additional_details' => 'عشاء عائلي',
            ]);
            
            // حجز خدمة العقارات
            $propertyBooking = Booking::create([
                'user_id' => $customers[0]->id,
                'service_id' => $property->id,
                'service_type' => 'property',
                'date' => now()->addDays(14),
                'time' => '15:00',
                'individuals_count' => 6,
                'total_amount' => 800.00,
                'status' => 'confirmed',
                'additional_details' => 'إجازة عائلية لمدة يومين',
            ]);
            
            // إنشاء الفواتير
            $bookings = [$entertainmentBooking, $cateringBooking, $restaurantBooking, $propertyBooking];
            
            foreach ($bookings as $booking) {
                Invoice::create([
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'total_amount' => $booking->total_amount,
                    'tax_amount' => $booking->total_amount * 0.15,
                    'discount_amount' => 0,
                    'commission_amount' => $booking->total_amount * 0.10,
                    'provider_amount' => $booking->total_amount * 0.75,
                    'platform_amount' => $booking->total_amount * 0.15,
                    'invoice_type' => 'customer',
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                    'transaction_id' => 'TXN' . time() . rand(1000, 9999),
                    'paid_at' => now(),
                ]);
            }
            
            // إنشاء التقييمات
            Review::create([
                'user_id' => $customers[0]->id,
                'reviewable_id' => $activity->id,
                'reviewable_type' => Activity::class,
                'rating' => 5,
                'comment' => 'خدمة ممتازة وحفلة رائعة!',
            ]);
            
            Review::create([
                'user_id' => $customers[1]->id,
                'reviewable_id' => $catering->id,
                'reviewable_type' => CateringItem::class,
                'rating' => 4,
                'comment' => 'الطعام لذيذ والتوصيل سريع',
            ]);
            
            Review::create([
                'user_id' => $customers[2]->id,
                'reviewable_id' => $restaurant->id,
                'reviewable_type' => Restaurant::class,
                'rating' => 5,
                'comment' => 'مطعم رائع وخدمة ممتازة',
            ]);
            
            // إنشاء المتابعات
            Follow::create([
                'follower_id' => $customers[0]->id,
                'following_id' => $entertainmentProvider->id,
            ]);
            
            Follow::create([
                'follower_id' => $customers[1]->id,
                'following_id' => $cateringProvider->id,
            ]);
            
            // إنشاء المفضلة
            Wish::create([
                'user_id' => $customers[0]->id,
                'wishable_id' => $property->id,
                'wishable_type' => Property::class,
            ]);
            
            Wish::create([
                'user_id' => $customers[1]->id,
                'wishable_id' => $catering->id,
                'wishable_type' => CateringItem::class,
            ]);
            
            // إنشاء المحادثات
            $conversation = Conversation::create([
                'participant1_id' => $entertainmentProvider->id,
                'participant2_id' => $cateringProvider->id,
                'last_message_at' => now(),
            ]);
            
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $entertainmentProvider->id,
                'content' => 'مرحباً، هل يمكننا التعاون في حفلة قادمة؟',
                'read_at' => null,
            ]);
            
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $cateringProvider->id,
                'content' => 'أهلاً وسهلاً، بالتأكيد! متى الحفلة؟',
                'read_at' => now(),
            ]);
            
            DB::commit();
            
            echo "✅ تم إنشاء البيانات الأساسية بنجاح!\n";
            echo "👤 المشرف: admin@gathro.com / 12345678\n";
            echo "🏢 مقدمي الخدمات:\n";
            echo "   - محمد الترفيهي: mohamed.entertainment@gathro.com / 12345678\n";
            echo "   - فاطمة الكيترينج: fatima.catering@gathro.com / 12345678\n";
            echo "   - علي المطعم: ali.restaurant@gathro.com / 12345678\n";
            echo "   - سارة العقارية: sara.property@gathro.com / 12345678\n";
            echo "👥 العملاء:\n";
            echo "   - أحمد العميل: ahmed.customer@gathro.com / 12345678\n";
            echo "   - سارة العميلة: sara.customer@gathro.com / 12345678\n";
            echo "   - خالد العميل: khalid.customer@gathro.com / 12345678\n";
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ خطأ في إنشاء البيانات: " . $e->getMessage() . "\n";
        }
    }
}
