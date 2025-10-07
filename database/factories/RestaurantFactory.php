<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'images' => '',
            'daily_available_bookings' => 50,
            'total_tables' => 20,
            'description' => $this->faker->sentence(8),
            'working_hours' => [
                'mon-thu' => ['12:00','23:00'],
                'fri' => ['16:00','23:59'],
                'sat' => ['12:00','23:59'],
                'sun' => ['12:00','23:00'],
            ],
            'available_tables_map' => null,
        ];
    }
}

