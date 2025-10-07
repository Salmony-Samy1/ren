<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantTableFactory extends Factory
{
    protected $model = RestaurantTable::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => $this->faker->bothify('T-###'),
            'type' => 'Normal',
            'capacity_people' => 4,
            'price_per_person' => 50,
            'price_per_table' => null,
            'quantity' => 1,
            're_availability_type' => 'AUTO',
            'auto_re_availability_minutes' => 15,
            'conditions' => [],
            'amenities' => [],
            'media' => [],
        ];
    }
}

