<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CateringItemCategory;

class CateringItemCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'أطباق رئيسية',
                'description' => 'الأطباق الرئيسية والوجبات الأساسية',
                'icon' => '🍽️',
                'sort_order' => 1,
            ],
            [
                'name' => 'مشروبات',
                'description' => 'المشروبات الباردة والساخنة',
                'icon' => '🥤',
                'sort_order' => 2,
            ],
            [
                'name' => 'حلويات',
                'description' => 'أنواع الحلويات المختلفة',
                'icon' => '🍰',
                'sort_order' => 3,
            ],
            [
                'name' => 'سلطات',
                'description' => 'أنواع السلطات والمقبلات',
                'icon' => '🥗',
                'sort_order' => 4,
            ],
            [
                'name' => 'مقبلات',
                'description' => 'المقبلات والوجبات الخفيفة',
                'icon' => '🥙',
                'sort_order' => 5,
            ],
            [
                'name' => 'فواكه',
                'description' => 'السلات الفواكه الموسمية',
                'icon' => '🍇',
                'sort_order' => 6,
            ],
            [
                'name' => 'قسمات إضافية',
                'description' => 'الأصناف الإضافية والخاصة',
                'icon' => '⭐',
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $category) {
            CateringItemCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
