<?php

namespace Database\Seeders;

use App\Models\CancellationPolicy;
use Illuminate\Database\Seeder;

class DefaultCancellationPolicySeeder extends Seeder
{
    public function run(): void
    {
        // Upsert a platform-wide default policy
        CancellationPolicy::updateOrCreate(
            ['service_id' => null],
            ['rules' => [
                ['threshold_hours' => 336, 'refund_percent' => 100], // 14 days
                ['threshold_hours' => 168, 'refund_percent' => 50],  // 7 days
                ['threshold_hours' => 72,  'refund_percent' => 25],  // 72 hours
            ]]
        );
    }
}

