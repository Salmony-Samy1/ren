<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nationality;

class NationalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nationalities = [
            ['name' => 'سعودي', 'name_en' => 'Saudi', 'code' => 'SAU', 'sort_order' => 1],
            ['name' => 'بحريني', 'name_en' => 'Bahraini', 'code' => 'BHR', 'sort_order' => 2],
            ['name' => 'أردني', 'name_en' => 'Jordanian', 'code' => 'JOR', 'sort_order' => 3],
            ['name' => 'لبناني', 'name_en' => 'Lebanese', 'code' => 'LBN', 'sort_order' => 4],
            ['name' => 'سوري', 'name_en' => 'Syrian', 'code' => 'SYR', 'sort_order' => 5],
            ['name' => 'عراقي', 'name_en' => 'Iraqi', 'code' => 'IRQ', 'sort_order' => 6],
            ['name' => 'كويتي', 'name_en' => 'Kuwaiti', 'code' => 'KWT', 'sort_order' => 7],
            ['name' => 'قطري', 'name_en' => 'Qatari', 'code' => 'QAT', 'sort_order' => 8],
            ['name' => 'إماراتي', 'name_en' => 'Emirati', 'code' => 'ARE', 'sort_order' => 9],
            ['name' => 'عماني', 'name_en' => 'Omani', 'code' => 'OMN', 'sort_order' => 10],
            ['name' => 'يمني', 'name_en' => 'Yemeni', 'code' => 'YEM', 'sort_order' => 11],
            ['name' => 'مصري', 'name_en' => 'Egyptian', 'code' => 'EGY', 'sort_order' => 12],
            ['name' => 'سوداني', 'name_en' => 'Sudanese', 'code' => 'SDN', 'sort_order' => 13],
            ['name' => 'مغربي', 'name_en' => 'Moroccan', 'code' => 'MAR', 'sort_order' => 14],
            ['name' => 'تونسي', 'name_en' => 'Tunisian', 'code' => 'TUN', 'sort_order' => 15],
            ['name' => 'جزائري', 'name_en' => 'Algerian', 'code' => 'DZA', 'sort_order' => 16],
            ['name' => 'ليبي', 'name_en' => 'Libyan', 'code' => 'LBY', 'sort_order' => 17],
            ['name' => 'فلسطيني', 'name_en' => 'Palestinian', 'code' => 'PSE', 'sort_order' => 18],
            ['name' => 'باكستاني', 'name_en' => 'Pakistani', 'code' => 'PAK', 'sort_order' => 19],
            ['name' => 'هندي', 'name_en' => 'Indian', 'code' => 'IND', 'sort_order' => 20],
            ['name' => 'بنغلاديشي', 'name_en' => 'Bangladeshi', 'code' => 'BGD', 'sort_order' => 21],
            ['name' => 'سريلانكي', 'name_en' => 'Sri Lankan', 'code' => 'LKA', 'sort_order' => 22],
            ['name' => 'فلبيني', 'name_en' => 'Filipino', 'code' => 'PHL', 'sort_order' => 23],
            ['name' => 'إندونيسي', 'name_en' => 'Indonesian', 'code' => 'IDN', 'sort_order' => 24],
            ['name' => 'ماليزي', 'name_en' => 'Malaysian', 'code' => 'MYS', 'sort_order' => 25],
            ['name' => 'تركي', 'name_en' => 'Turkish', 'code' => 'TUR', 'sort_order' => 26],
            ['name' => 'إيراني', 'name_en' => 'Iranian', 'code' => 'IRN', 'sort_order' => 27],
            ['name' => 'أفغاني', 'name_en' => 'Afghan', 'code' => 'AFG', 'sort_order' => 28],
            ['name' => 'أثيوبي', 'name_en' => 'Ethiopian', 'code' => 'ETH', 'sort_order' => 29],
            ['name' => 'إريتري', 'name_en' => 'Eritrean', 'code' => 'ERI', 'sort_order' => 30],
            ['name' => 'صومالي', 'name_en' => 'Somali', 'code' => 'SOM', 'sort_order' => 31],
            ['name' => 'كينيا', 'name_en' => 'Kenyan', 'code' => 'KEN', 'sort_order' => 32],
            ['name' => 'أوغندي', 'name_en' => 'Ugandan', 'code' => 'UGA', 'sort_order' => 33],
            ['name' => 'تنزاني', 'name_en' => 'Tanzanian', 'code' => 'TZA', 'sort_order' => 34],
            ['name' => 'نيجيري', 'name_en' => 'Nigerian', 'code' => 'NGA', 'sort_order' => 35],
            ['name' => 'غاني', 'name_en' => 'Ghanaian', 'code' => 'GHA', 'sort_order' => 36],
            ['name' => 'سنغالي', 'name_en' => 'Senegalese', 'code' => 'SEN', 'sort_order' => 37],
            ['name' => 'مالي', 'name_en' => 'Malian', 'code' => 'MLI', 'sort_order' => 38],
            ['name' => 'بوركينابي', 'name_en' => 'Burkinabé', 'code' => 'BFA', 'sort_order' => 39],
            ['name' => 'نيجيري من النيجر', 'name_en' => 'Nigerien', 'code' => 'NER', 'sort_order' => 40],
            ['name' => 'تشادي', 'name_en' => 'Chadian', 'code' => 'TCD', 'sort_order' => 41],
            ['name' => 'كاميروني', 'name_en' => 'Cameroonian', 'code' => 'CMR', 'sort_order' => 42],
            ['name' => 'كونغولي', 'name_en' => 'Congolese', 'code' => 'COD', 'sort_order' => 43],
            ['name' => 'رواندي', 'name_en' => 'Rwandan', 'code' => 'RWA', 'sort_order' => 44],
            ['name' => 'بوروندي', 'name_en' => 'Burundian', 'code' => 'BDI', 'sort_order' => 45],
            ['name' => 'مدغشقري', 'name_en' => 'Malagasy', 'code' => 'MDG', 'sort_order' => 46],
            ['name' => 'موريشيوسي', 'name_en' => 'Mauritian', 'code' => 'MUS', 'sort_order' => 47],
            ['name' => 'سيشيلي', 'name_en' => 'Seychellois', 'code' => 'SYC', 'sort_order' => 48],
            ['name' => 'جيبوتي', 'name_en' => 'Djiboutian', 'code' => 'DJI', 'sort_order' => 49],
            ['name' => 'أخرى', 'name_en' => 'Other', 'code' => 'OTH', 'sort_order' => 999],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::firstOrCreate(
                ['code' => $nationality['code']],
                $nationality
            );
        }
    }
}
