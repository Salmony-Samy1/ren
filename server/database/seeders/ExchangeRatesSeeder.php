<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExchangeRate;

class ExchangeRatesSeeder extends Seeder
{
    public function run(): void
    {
        // Seed basic rates between SAR and BHD (example only)
        ExchangeRate::updateOrCreate(['base_currency' => 'SAR', 'quote_currency' => 'BHD'], ['rate' => 0.1000]);
        ExchangeRate::updateOrCreate(['base_currency' => 'BHD', 'quote_currency' => 'SAR'], ['rate' => 10.0000]);
    }
}

