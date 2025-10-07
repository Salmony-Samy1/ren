<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MainService;
use App\Models\Category;

class MainServicesAndCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Create Main Services with Arabic and English names
        $services = [
            [
                'name' => 'فعاليات',
                'name_en' => 'Events',
                'description' => 'الخدمات المتعلقة بالفعاليات والحفلات',
                'description_en' => 'All event-related services',
                'categories' => [
                    ['ar' => ['name' => 'حفلات'], 'en' => ['name' => 'Parties']],
                    ['ar' => ['name' => 'مؤتمرات'], 'en' => ['name' => 'Conferences']],
                    ['ar' => ['name' => 'معارض'], 'en' => ['name' => 'Exhibitions']],
                    ['ar' => ['name' => 'مهرجانات'], 'en' => ['name' => 'Festivals']],
                    ['ar' => ['name' => 'ورش عمل'], 'en' => ['name' => 'Workshops']],
                    ['ar' => ['name' => 'حفلات تخرج'], 'en' => ['name' => 'Graduations']],
                ],
            ],
            [
                'name' => 'كيترينج',
                'name_en' => 'Catering',
                'description' => 'تقديم الطعام والضيافة',
                'description_en' => 'Food and hospitality',
                'categories' => [
                    ['ar' => ['name' => 'بوفيه'], 'en' => ['name' => 'Buffet']],
                    ['ar' => ['name' => 'مأكولات محلية'], 'en' => ['name' => 'Local Cuisine']],
                    ['ar' => ['name' => 'حلويات'], 'en' => ['name' => 'Desserts']],
                    ['ar' => ['name' => 'حفلات صغيرة'], 'en' => ['name' => 'Private Parties']],
                    ['ar' => ['name' => 'سحور/فطور'], 'en' => ['name' => 'Suhur/Breakfast']],
                    ['ar' => ['name' => 'مأكولات بحرية'], 'en' => ['name' => 'Seafood']],
                ],
            ],
            [
                'name' => 'مطاعم وحجز طاولات',
                'name_en' => 'Restaurants & Tables',
                'description' => 'مطاعم وحجوزات الطاولات',
                'description_en' => 'Restaurants and table bookings',
                'categories' => [
                    ['ar' => ['name' => 'فاين دايننج'], 'en' => ['name' => 'Fine Dining']],
                    ['ar' => ['name' => 'عائلات'], 'en' => ['name' => 'Family']],
                    ['ar' => ['name' => 'مقاهي'], 'en' => ['name' => 'Cafes']],
                    ['ar' => ['name' => 'مأكولات سريعة'], 'en' => ['name' => 'Fast Food']],
                    ['ar' => ['name' => 'إيطالي'], 'en' => ['name' => 'Italian']],
                    ['ar' => ['name' => 'هندي/آسيوي'], 'en' => ['name' => 'Indian/Asian']],
                ],
            ],
            [
                'name' => 'شقق وشاليهات',
                'name_en' => 'Stays (Apartments & Chalets)',
                'description' => 'إقامات وشاليهات ووحدات سكنية',
                'description_en' => 'Stays including apartments and chalets',
                'categories' => [
                    ['ar' => ['name' => 'شاليهات'], 'en' => ['name' => 'Chalets']],
                    ['ar' => ['name' => 'فلل'], 'en' => ['name' => 'Villas']],
                    ['ar' => ['name' => 'شقق'], 'en' => ['name' => 'Apartments']],
                    ['ar' => ['name' => 'مزارع'], 'en' => ['name' => 'Farms']],
                    ['ar' => ['name' => 'استوديوهات'], 'en' => ['name' => 'Studios']],
                    ['ar' => ['name' => 'منتجعات'], 'en' => ['name' => 'Resorts']],
                ],
            ],
        ];

        foreach ($services as $svc) {
            $main = MainService::firstOrCreate(
                ['name' => $svc['name']],
                [
                    'name_en' => $svc['name_en'],
                    'description' => $svc['description'] ?? null,
                    'description_en' => $svc['description_en'] ?? null,
                ]
            );

            foreach ($svc['categories'] as $catData) {
                $cat = new Category(['status' => true]);
                $cat->main_service_id = $main->id;
                $cat->save();
                // translations
                if (isset($catData['ar'])) {
                    $cat->translateOrNew('ar')->name = $catData['ar']['name'];
                    $cat->translateOrNew('ar')->description = $catData['ar']['description'] ?? null;
                }
                if (isset($catData['en'])) {
                    $cat->translateOrNew('en')->name = $catData['en']['name'];
                    $cat->translateOrNew('en')->description = $catData['en']['description'] ?? null;
                }
                $cat->save();
            }
        }
    }
}

