<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Neigbourhood;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run()
    {
        // الدول العربية الثلاث
        $countries = [
            [
                'name_ar' => 'المملكة العربية السعودية',
                'name_en' => 'Saudi Arabia',
                'code' => '+966',
                'iso_code' => 'SA',
                'currency_code' => 'SAR',
                'currency_name_ar' => 'الريال السعودي',
                'currency_name_en' => 'Saudi Riyal',
                'currency_symbol' => '﷼',
                'exchange_rate' => 1.000000,
                'flag_emoji' => '🇸🇦',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name_ar' => 'مملكة البحرين',
                'name_en' => 'Bahrain',
                'code' => '+973',
                'iso_code' => 'BH',
                'currency_code' => 'BHD',
                'currency_name_ar' => 'الدينار البحريني',
                'currency_name_en' => 'Bahraini Dinar',
                'currency_symbol' => 'د.ب',
                'exchange_rate' => 0.100000,
                'flag_emoji' => '🇧🇭',
                'timezone' => 'Asia/Bahrain',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name_ar' => 'دولة الإمارات العربية المتحدة',
                'name_en' => 'United Arab Emirates',
                'code' => '+971',
                'iso_code' => 'AE',
                'currency_code' => 'AED',
                'currency_name_ar' => 'الدرهم الإماراتي',
                'currency_name_en' => 'UAE Dirham',
                'currency_symbol' => 'د.إ',
                'exchange_rate' => 1.000000,
                'flag_emoji' => '🇦🇪',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
                'sort_order' => 3
            ]
        ];

        foreach ($countries as $countryData) {
            $country = Country::create($countryData);
            
            // إضافة الترجمة
            $country->translateOrNew('en')->name = $countryData['name_en'];
            $country->translateOrNew('ar')->name = $countryData['name_ar'];
            $country->save();
        }

        // إضافة مدينة افتراضية لكل دولة (للتوافق مع النظام الحالي)
        $saudiCountry = Country::where('iso_code', 'SA')->first();
        if ($saudiCountry) {
            $city = new City();
            $city->is_active = true;
            $city->country_id = $saudiCountry->id;
            $city->save();
            $city->translateOrNew('en')->name = 'Riyadh';
            $city->translateOrNew('ar')->name = 'الرياض';
            $city->save();
        }
    }
}
