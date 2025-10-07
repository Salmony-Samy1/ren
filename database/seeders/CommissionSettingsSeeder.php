<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommissionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'commission_type' => 'percentage',
            'commission_amount' => 5,
            'event_commission_rate' => 3,
            'catering_commission_rate' => 4,
            'restaurant_commission_rate' => 5,
            'property_commission_rate' => 6,
            'volume_1000_rate' => 1,
            'volume_5000_rate' => 2,
            'volume_10000_rate' => 3,
            'rating_4_rate' => 1,
            'rating_4_5_rate' => 2,
            'rating_5_rate' => 3,
            'min_commission' => 0,
            'max_commission' => 50,
            'max_commission_amount' => 100,
            'tax_rate' => 15,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Settings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
