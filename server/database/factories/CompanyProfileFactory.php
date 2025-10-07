<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use App\Models\Country;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyProfileFactory extends Factory
{
    protected $model = CompanyProfile::class;

    public function definition(): array
    {
        $country = Country::factory()->create();
        $city = \App\Models\City::factory()->create(['country_id' => $country->id]);
        return [
            'name' => $this->faker->company(),
            'owner' => $this->faker->name(),
            'national_id' => (string)$this->faker->numerify('##########'),
            'country_id' => $country->id,
            'city_id' => $city->id,
        ];
    }
}

