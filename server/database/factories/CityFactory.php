<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        $country = Country::factory()->create();
        return [
            'country_id' => $country->id,
            'is_active' => true,
        ];
    }
}

