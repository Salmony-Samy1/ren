<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'subtotal' => 100,
            'tax' => 0,
            'discount' => 0,
            'total' => 100,
            'status' => 'confirmed',
            'payment_method' => 'wallet',
        ];
    }
}

