<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalIdVerificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'national_id_verification_enabled',
                'value' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'national_id_verification_gateway',
                'value' => 'testing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'national_id_verification_cache_duration',
                'value' => '24',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'national_id_verification_auto_approve',
                'value' => 'false',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'national_id_verification_required_for_approval',
                'value' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
