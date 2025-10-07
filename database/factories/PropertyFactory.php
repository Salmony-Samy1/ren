<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'service_id' => \App\Models\Service::factory(),
            'property_name' => $this->faker->streetName(),
            'type' => 'شاليه',
            'category' => 'عقار',
            'images' => [],
            'unit_code' => $this->faker->unique()->bothify('UNIT-####'),
            'area_sqm' => 120,
            'down_payment_percentage' => 0,
            'is_refundable_insurance' => false,
            'cancellation_policy' => 'flexible',
            'description' => $this->faker->sentence(6),
            'allowed_category' => 'family',
            'room_details' => [],
            'facilities' => [],
            'access_instructions' => 'door code 1234',
            'checkin_time' => '15:00',
            'checkout_time' => '12:00',
            // Pricing fields added by later migration
            'nightly_price' => 100,
            'max_adults' => 4,
            'max_children' => 2,
        ];
    }
}

