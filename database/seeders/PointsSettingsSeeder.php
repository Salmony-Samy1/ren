<?php

namespace Database\Seeders;

use App\Enums\SettingKeys;
use Illuminate\Database\Seeder;

class PointsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            SettingKeys::LOYALTY_POINTS->value => 10,
            SettingKeys::FIRST_BOOKING_POINTS->value => 50,
            SettingKeys::REVIEW_POINTS->value => 5,
            SettingKeys::REFERRAL_POINTS->value => 100,
            SettingKeys::POINTS_TO_WALLET_RATE->value => 0.1,
            SettingKeys::MIN_POINTS_FOR_CONVERSION->value => 100,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Settings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
