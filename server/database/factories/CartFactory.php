<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'open',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
            'meta' => null,
        ];
    }
}

