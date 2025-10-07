<?php

namespace Database\Seeders;

use App\Enums\SettingKeys;
use Illuminate\Database\Seeder;

class ReferralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'referral_enabled' => true,
            'referral_points' => 100,
            'referral_min_booking' => 1,
            'referral_expiry_days' => 365,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Settings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
