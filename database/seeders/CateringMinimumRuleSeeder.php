<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CateringMinimumRule;
use App\Models\User;

class CateringMinimumRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create general rules (for the system)
        $generalRules = [
            [
                'provider_id' => null,
                'rule_name' => 'قاعدة الرياض الأساسية',
                'region_name' => 'الرياض - وسط المدينة',
                'city' => 'الرياض',
                'center_lat' => 24.7136,
                'center_long' => 46.6753,
                'radius_km' => 15,
                'min_order_value' => 500.00,
                'delivery_fee' => 50.00,
                'free_delivery_threshold' => 1000.00,
                'max_delivery_distance_km' => 20,
                'operating_hours' => [
                    'sunday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'monday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'tuesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'wednesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'thursday' => ['start' => '08:00', 'end' => '01:00', 'is_active' => true],
                    'friday' => ['start' => '14:00', 'end' => '01:00', 'is_active' => true],
                    'saturday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
                ],
                'special_conditions' => [
                    'يتم تطبيق رسوم إضافية للطلبات خارج الوقت العادي',
                    'لا يوجد توصيل للمناطق الصناعية بعد الساعة 22:00'
                ],
                'is_active' => true,
                'status' => 'active',
                'created_by_admin' => true,
                'applied_orders_count' => 234,
                'total_revenue_impact' => 45000.00,
            ],
            [
                'provider_id' => null,
                'rule_name' => 'قاعدة جدة الساحلية',
                'region_name' => 'جدة - المناطق الساحلية',
                'city' => 'جدة',
                'center_lat' => 21.5169,
                'center_long' => 39.2192,
                'radius_km' => 25,
                'min_order_value' => 300.00,
                'delivery_fee' => 30.00,
                'free_delivery_threshold' => 700.00,
                'max_delivery_distance_km' => 30,
                'operating_hours' => [
                    'sunday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
                    'monday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
                    'tuesday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
                    'wednesday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
                    'thursday' => ['start' => '10:00', 'end' => '02:00', 'is_active' => true],
                    'friday' => ['start' => '14:00', 'end' => '02:00', 'is_active' => true],
                    'saturday' => ['start' => '10:00', 'end' => '02:00', 'is_active' => true],
                ],
                'special_conditions' => [
                    'رسوم إضافية للمنازل الطابقية الثالث وأعلى',
                    'خدمة التوصيل متاحة في عطلة نهاية الأسبوع حتى الساعة 02:00'
                ],
                'is_active' => true,
                'status' => 'active',
                'created_by_admin' => true,
                'applied_orders_count' => 156,
                'total_revenue_impact' => 28000.00,
            ],
            [
                'provider_id' => null,
                'rule_name' => 'قاعدة الدمام الصناعية',
                'region_name' => 'الدمام - المناطق الصناعية',
                'city' => 'الدمام',
                'center_lat' => 26.4207,
                'center_long' => 50.0888,
                'radius_km' => 20,
                'min_order_value' => 400.00,
                'delivery_fee' => 40.00,
                'free_delivery_threshold' => 800.00,
                'max_delivery_distance_km' => 25,
                'operating_hours' => [
                    'sunday' => ['start' => '07:00', 'end' => '22:00', 'is_active' => true],
                    'monday' => ['start' => '07:00', 'end' => '22:00', 'is_active' => true],
                    'tuesday' => ['start' => '07:00', 'end' => '22:00', 'is_active' => true],
                    'wednesday' => ['start' => '07:00', 'end' => '22:00', 'is_active' => true],
                    'thursday' => ['start' => '07:00', 'end' => '22:00', 'is_active' => true],
                    'friday' => ['start' => '09:00', 'end' => '22:00', 'is_active' => true],
                    'saturday' => ['start' => '09:00', 'end' => '22:00', 'is_active' => true],
                ],
                'special_conditions' => [
                    'رسوم توصيل مزدوجة للمناطق الصناعية البعيدة',
                    'مواعيد التوصيل محدودة بسبب قيود المرور'
                ],
                'is_active' => true,
                'status' => 'active',
                'created_by_admin' => true,
                'applied_orders_count' => 89,
                'total_revenue_impact' => 19500.00,
            ]
        ];

        foreach ($generalRules as $rule) {
            CateringMinimumRule::create($rule);
        }

        // Create provider-specific rules if providers exist
        $provider = User::where('type', 'provider')->first();
        if ($provider) {
            $providerRules = [
                [
                    'provider_id' => $provider->id,
                    'rule_name' => 'قاعدة خاصة للمطعم الاساسي',
                    'region_name' => 'الرياض - الحي الدبلوماسي',
                    'city' => 'الرياض',
                    'center_lat' => 24.6986,
                    'center_long' => 46.6902,
                    'radius_km' => 10,
                    'min_order_value' => 800.00,
                    'delivery_fee' => 75.00,
                    'free_delivery_threshold' => 1500.00,
                    'max_delivery_distance_km' => 15,
                    'operating_hours' => [
                        'sunday' => ['start' => '12:00', 'end' => '24:00', 'is_active' => true],
                        'monday' => ['start' => '12:00', 'end' => '24:00', 'is_active' => true],
                        'tuesday' => ['start' => '12:00', 'end' => '24:00', 'is_active' => true],
                        'wednesday' => ['start' => '12:00', 'end' => '24:00', 'is_active' => true],
                        'thursday' => ['start' => '12:00', 'end' => '02:00', 'is_active' => true],
                        'friday' => ['start' => '16:00', 'end' => '02:00', 'is_active' => true],
                        'saturday' => ['start' => '14:00', 'end' => '02:00', 'is_active' => true],
                    ],
                    'special_conditions' => [
                        'خدمة VIP للحي الدبلوماسي',
                        'توصيل مجاني لكل الطلبات فوق 1500 ريال'
                    ],
                    'is_active' => true,
                    'status' => 'active',
                    'created_by_admin' => true,
                    'applied_orders_count' => 67,
                    'total_revenue_impact' => 32000.00,
                ]
            ];

            foreach ($providerRules as $rule) {
                CateringMinimumRule::create($rule);
            }
        }
    }
}