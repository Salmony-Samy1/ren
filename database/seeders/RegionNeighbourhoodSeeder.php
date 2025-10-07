<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegionNeighbourhoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks to avoid constraint issues during truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data from all related tables
        DB::table('neigbourhood_translations')->truncate();
        DB::table('neigbourhoods')->truncate();
        DB::table('region_translations')->truncate();
        DB::table('regions')->truncate();
        DB::table('city_translations')->truncate();
        DB::table('cities')->truncate();
        DB::table('country_translations')->truncate();
        DB::table('countries')->truncate();

        $now = Carbon::now();

        // Use a nested array structure for a more scalable seeding approach
        $locationData = [
            'countries' => [
                [
                    'en_name' => 'Bahrain',
                    'ar_name' => 'البحرين',
                    'cities' => [
                        ['en_name' => 'Manama', 'ar_name' => 'المنامة'],
                        ['en_name' => 'Muharraq', 'ar_name' => 'المحرق'],
                        ['en_name' => 'Riffa', 'ar_name' => 'الرفاع'],
                        ['en_name' => 'Hamad Town', 'ar_name' => 'مدينة حمد'],
                        ['en_name' => 'A\'ali', 'ar_name' => 'عالي'],
                        ['en_name' => 'Isa Town', 'ar_name' => 'مدينة عيسى'],
                        ['en_name' => 'Sitra', 'ar_name' => 'سترة'],
                        ['en_name' => 'Jidhafs', 'ar_name' => 'جد حفص'],
                        ['en_name' => 'Budaiya', 'ar_name' => 'البديع'],
                        ['en_name' => 'Al Hidd', 'ar_name' => 'الحد'],
                        ['en_name' => 'Bani Jamrah', 'ar_name' => 'بني جمرة'],
                        ['en_name' => 'Karzakkan', 'ar_name' => 'كرزكان'],
                        ['en_name' => 'Malkiya', 'ar_name' => 'المالكية'],
                        ['en_name' => 'Adliya', 'ar_name' => 'العدلية'],
                        ['en_name' => 'Juffair', 'ar_name' => 'الجفير'],
                        ['en_name' => 'Amwaj Islands', 'ar_name' => 'جزر أمواج'],
                        ['en_name' => 'Diyar Al Muharraq', 'ar_name' => 'ديار المحرق'],
                        ['en_name' => 'Zallaq', 'ar_name' => 'الزلاق'],
                        ['en_name' => 'Arad', 'ar_name' => 'عراد'],
                        ['en_name' => 'Galali', 'ar_name' => 'قلالي'],
                        ['en_name' => 'Samaheej', 'ar_name' => 'سماهيج'],
                        ['en_name' => 'Tubli', 'ar_name' => 'توبلي'],
                        ['en_name' => 'Salmabad', 'ar_name' => 'سلماباد'],
                        ['en_name' => 'Jurdab', 'ar_name' => 'جرداب'],
                        ['en_name' => 'Dar Kulaib', 'ar_name' => 'دار كليب'],
                    ],
                ],
                [
                    'en_name' => 'Saudi Arabia',
                    'ar_name' => 'المملكة العربية السعودية',
                    'cities' => [
                        ['en_name' => 'Riyadh', 'ar_name' => 'الرياض'],
                        ['en_name' => 'Jeddah', 'ar_name' => 'جدة'],
                        ['en_name' => 'Mecca', 'ar_name' => 'مكة المكرمة'],
                        ['en_name' => 'Medina', 'ar_name' => 'المدينة المنورة'],
                        ['en_name' => 'Dammam', 'ar_name' => 'الدمام'],
                        ['en_name' => 'Tabuk', 'ar_name' => 'تبوك'],
                        ['en_name' => 'Al Hufuf', 'ar_name' => 'الهفوف'],
                        ['en_name' => 'Al Qatif', 'ar_name' => 'القطيف'],
                        ['en_name' => 'Taif', 'ar_name' => 'الطائف'],
                        ['en_name' => 'Al Jubayl', 'ar_name' => 'الجبيل'],
                        ['en_name' => 'Buraydah', 'ar_name' => 'بريدة'],
                        ['en_name' => 'Hafr al Batin', 'ar_name' => 'حفر الباطن'],
                        ['en_name' => 'Yanbu', 'ar_name' => 'ينبع'],
                        ['en_name' => 'Hail', 'ar_name' => 'حائل'],
                        ['en_name' => 'Abha', 'ar_name' => 'أبها'],
                        ['en_name' => 'Sakaka', 'ar_name' => 'سكاكا'],
                        ['en_name' => 'Al Qurayyat', 'ar_name' => 'القريات'],
                        ['en_name' => 'Jazan', 'ar_name' => 'جازان'],
                        ['en_name' => 'Najran', 'ar_name' => 'نجران'],
                        ['en_name' => 'Al Wajh', 'ar_name' => 'الوجه'],
                        ['en_name' => 'Arar', 'ar_name' => 'عرعر'],
                        ['en_name' => 'Al Bahah', 'ar_name' => 'الباحة'],
                        ['en_name' => 'Khobar', 'ar_name' => 'الخبر'],
                        ['en_name' => 'Khamis Mushait', 'ar_name' => 'خميس مشيط'],
                        ['en_name' => 'Unaizah', 'ar_name' => 'عنيزة'],
                        ['en_name' => 'Bisha', 'ar_name' => 'بيشة'],
                        ['en_name' => 'Diriyah', 'ar_name' => 'الدرعية'],
                        ['en_name' => 'Al Lith', 'ar_name' => 'الليث'],
                        ['en_name' => 'Al Majma\'ah', 'ar_name' => 'المجمعة'],
                        ['en_name' => 'Rabigh', 'ar_name' => 'رابغ'],
                        ['en_name' => 'Ras Tanura', 'ar_name' => 'رأس تنورة'],
                        ['en_name' => 'Dhahran', 'ar_name' => 'الظهران'],
                        ['en_name' => 'Shaqra', 'ar_name' => 'شقراء'],
                        ['en_name' => 'Umluj', 'ar_name' => 'أملج'],
                        ['en_name' => 'Al Ula', 'ar_name' => 'العلا'],
                        ['en_name' => 'Dawadmi', 'ar_name' => 'الدوادمي'],
                        ['en_name' => 'Zulfi', 'ar_name' => 'الزلفي'],
                        ['en_name' => 'Ad Dilam', 'ar_name' => 'الدلم'],
                        ['en_name' => 'Al Kharj', 'ar_name' => 'الخرج'],
                        ['en_name' => 'Haql', 'ar_name' => 'حقل'],
                        ['en_name' => 'Tayma', 'ar_name' => 'تيماء'],
                        ['en_name' => 'Turaif', 'ar_name' => 'طريف'],
                        ['en_name' => 'Sabya', 'ar_name' => 'صبيا'],
                        ['en_name' => 'Sharurah', 'ar_name' => 'شرورة'],
                        ['en_name' => 'Wadi Al Dawasir', 'ar_name' => 'وادي الدواسر'],
                    ],
                ],
            ],
        ];

        // Loop through the data to insert all locations
        foreach ($locationData['countries'] as $countryData) {
            $countryId = DB::table('countries')->insertGetId([
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('country_translations')->insert([
                ['country_id' => $countryId, 'locale' => 'en', 'name' => $countryData['en_name'], 'created_at' => $now, 'updated_at' => $now],
                ['country_id' => $countryId, 'locale' => 'ar', 'name' => $countryData['ar_name'], 'created_at' => $now, 'updated_at' => $now],
            ]);

            foreach ($countryData['cities'] as $cityData) {
                $cityId = DB::table('cities')->insertGetId([
                    'country_id' => $countryId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                
                DB::table('city_translations')->insert([
                    ['city_id' => $cityId, 'locale' => 'en', 'name' => $cityData['en_name'], 'created_at' => $now, 'updated_at' => $now],
                    ['city_id' => $cityId, 'locale' => 'ar', 'name' => $cityData['ar_name'], 'created_at' => $now, 'updated_at' => $now],
                ]);

                // Add a single default region and neighborhood for each city to keep the structure
                $regionId = DB::table('regions')->insertGetId([
                    'city_id' => $cityId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('region_translations')->insert([
                    ['region_id' => $regionId, 'locale' => 'en', 'name' => $cityData['en_name'] . ' Central', 'created_at' => $now, 'updated_at' => $now],
                    ['region_id' => $regionId, 'locale' => 'ar', 'name' => 'المنطقة الوسطى ل' . $cityData['ar_name'], 'created_at' => $now, 'updated_at' => $now],
                ]);

                $neighbourhoodId = DB::table('neigbourhoods')->insertGetId([
                    'region_id' => $regionId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('neigbourhood_translations')->insert([
                    ['neigbourhood_id' => $neighbourhoodId, 'locale' => 'en', 'name' => $cityData['en_name'] . ' Central District', 'created_at' => $now, 'updated_at' => $now],
                    ['neigbourhood_id' => $neighbourhoodId, 'locale' => 'ar', 'name' => 'حي المنطقة الوسطى ل' . $cityData['ar_name'], 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
