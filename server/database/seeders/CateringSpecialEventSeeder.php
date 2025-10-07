<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CateringSpecialEvent;
use App\Models\User;
use App\Models\Service;
use Carbon\Carbon;

class CateringSpecialEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing provider and service
        $provider = User::where('type', 'provider')->first();
        $service = Service::first();

        if (!$provider || !$service) {
            return;
        }

        $specialEvents = [
            [
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'event_name' => 'حفل زفاف السيد محمد بن أحمد',
                'event_type' => 'wedding',
                'client_name' => 'محمد أحمد الشهري',
                'client_phone' => '+966501234567',
                'client_email' => 'mohammed@example.com',
                'event_datetime' => Carbon::now()->addMonths(2)->setTime(18, 0),
                'venue_name' => 'قاعة الأمير للاحتفالات',
                'full_address' => 'طريق الملك فهد، صالة الأمير، الرياض',
                'event_city' => 'الرياض',
                'event_lat' => 24.774265,
                'event_long' => 46.738586,
                'guest_count' => 300,
                'estimated_budget' => 45000.00,
                'confirmed_budget' => 48000.00,
                'status' => 'confirmed',
                'progress_percentage' => 45,
                'special_requirements' => [
                    'مأكولات حلال مؤكدة',
                    'خدمات التقديم الفاخرة',
                    'ديكور بالورود البيضاء'
                ],
                'menu_items' => [
                    [
                        'name' => 'كبدة مشوية بالسمسم',
                        'quantity' => 500,
                        'unit' => 'portion',
                        'special_notes' => 'مطبوخة بشكل مثالي'
                    ],
                    [
                        'name' => 'مندي الدجاج',
                        'quantity' => 200,
                        'unit' => 'plate',
                        'special_notes' => 'حار متوسط'
                    ]
                ],
                'timeline' => [
                    [
                        'milestone' => 'تأكيد التفاصيل النهائية',
                        'due_date' => Carbon::now()->addDays(10),
                        'completed' => true
                    ],
                    [
                        'milestone' => 'طلب اللوازم',
                        'due_date' => Carbon::now()->addDays(30),
                        'completed' => false
                    ],
                    [
                        'milestone' => 'التجهيز والطبخ',
                        'due_date' => Carbon::now()->addMonths(2)->subDays(1),
                        'completed' => false
                    ]
                ],
                'contact_persons' => [
                    [
                        'name' => 'محمد أحمد الشهري',
                        'role' => 'العريس',
                        'phone' => '+966501234567',
                        'is_primary' => true
                    ],
                    [
                        'name' => 'فاطمة أحمد الشهري',
                        'role' => 'والدة العريس',
                        'phone' => '+966501234568',
                        'is_primary' => false
                    ]
                ],
                'admin_notes' => 'مناسبة خاصة تتطلب اهتمام وعناية مركزة',
                'created_by_admin' => true,
                'customer_id' => null,
            ],
            [
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'event_name' => 'مؤتمر الشركات التقنية',
                'event_type' => 'conference',
                'client_name' => 'شراة التقنيات المتقدمة',
                'client_phone' => '+966507654321',
                'client_email' => 'events@tech-advanced.com',
                'event_datetime' => Carbon::now()->addMonths(1)->setTime(9, 0),
                'venue_name' => 'فندق الفيصلية الجناح الملكي',
                'full_address' => 'طريق الملك فهد، فندق الفيصلية، الرياض',
                'event_city' => 'الرياض',
                'event_lat' => 24.774265,
                'event_long' => 46.738586,
                'guest_count' => 150,
                'estimated_budget' => 25000.00,
                'confirmed_budget' => 22000.00,
                'status' => 'planning',
                'progress_percentage' => 30,
                'special_requirements' => [
                    'كوفي بريك متكررة',
                    'طعام مناسب للثقافات المختلفة',
                    'مشروبات ساخنة متوفرة طوال اليوم'
                ],
                'menu_items' => [
                    [
                        'name' => 'بطيف الإفطار الدولي',
                        'quantity' => 150,
                        'unit' => 'portion',
                        'special_notes' => 'يشمل خيارات خالية من الغلوتين'
                    ]
                ],
                'timeline' => [
                    [
                        'milestone' => 'تأكيد القاعة والمعدات',
                        'due_date' => Carbon::now()->addDays(5),
                        'completed' => false
                    ],
                    [
                        'milestone' => 'قائمة الطعام النهائية',
                        'due_date' => Carbon::now()->addDays(15),
                        'completed' => false
                    ]
                ],
                'contact_persons' => [
                    [
                        'name' => 'خالد النعيمي',
                        'role' => 'مدير الفعاليات',
                        'phone' => '+966507654321',
                        'is_primary' => true
                    ]
                ],
                'admin_notes' => 'هذه أول مرة نتعامل مع هذا العميل - يحتاج متابعة عن كثب',
                'created_by_admin' => true,
                'customer_id' => null,
            ],
            [
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'event_name' => 'حفل تخرّج الجامعة',
                'event_type' => 'private_celebration',
                'client_name' => 'جامعة الملك سعود',
                'client_phone' => '+966112347890',
                'client_email' => 'events@ksu.edu.sa',
                'event_datetime' => Carbon::now()->addDays(20)->setTime(19, 0),
                'venue_name' => 'قاعة الملك عبدالله للمؤتمرات',
                'full_address' => 'جامعة الملك سعود، طريق الملك عبدالعزيز، الرياض',
                'event_city' => 'الرياض',
                'event_lat' => 24.7136,
                'event_long' => 46.6753,
                'guest_count' => 500,
                'estimated_budget' => 32000.00,
                'status' => 'inquiry',
                'progress_percentage' => 15,
                'special_requirements' => [
                    'طعام مناسب لجميع الأعمار',
                    'خدمة ساخنة وسريعة لعدد كبير من الضيوف',
                    'تغليف جميل للضيافة'
                ],
                'menu_items' => [
                    [
                        'name' => 'قمه التخرج التقليدية',
                        'quantity' => 500,
                        'unit' => 'portion',
                        'special_notes' => 'متنوعة تتناسب مع جميع الأذواق'
                    ]
                ],
                'timeline' => [
                    [
                        'milestone' => 'استلام التفاصيل النهائية',
                        'due_date' => Carbon::now()->addDays(14),
                        'completed' => false
                    ]
                ],
                'contact_persons' => [
                    [
                        'name' => 'الدكتورة نورا العنزي',
                        'role' => 'منسقة الشؤون الطلابية',
                        'phone' => '+966112347890',
                        'is_primary' => true
                    ]
                ],
                'admin_notes' => 'حدث جامعي مهم - يحتاج تنظيم دقيق',
                'created_by_admin' => true,
                'customer_id' => null,
            ]
        ];

        foreach ($specialEvents as $event) {
            CateringSpecialEvent::create($event);
        }
    }
}