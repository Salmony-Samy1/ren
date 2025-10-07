<?php

namespace Database\Factories;

use App\Models\MainService;
use Illuminate\Database\Eloquent\Factories\Factory;

class MainServiceFactory extends Factory
{
    protected $model = MainService::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}

