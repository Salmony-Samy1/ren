<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Cart;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'service_id' => Service::factory(),
            'quantity' => 1,
            'start_date' => null,
            'end_date' => null,
            'unit_price' => 0,
            'tax' => 0,
            'discount' => 0,
            'line_total' => 0,
            'meta' => null,
        ];
    }
}

