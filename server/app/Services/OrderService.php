<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createFromCart(Cart $cart, array $summary): Order
    {
        return DB::transaction(function () use ($cart, $summary) {
            $order = Order::create([
                'user_id' => $cart->user_id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => (float) $cart->subtotal,
                'tax_total' => (float) $cart->tax_total,
                'discount_total' => (float) $cart->discount_total,
                'grand_total' => (float) $cart->grand_total,
                'payable_total' => (float) ($summary['payable_total'] ?? $cart->grand_total),
                'coupon_code' => $summary['applied']['coupon']['code'] ?? null,
                'coupon_discount' => (float) ($summary['applied']['coupon']['discount'] ?? 0),
                'points_used' => (int) ($summary['applied']['points']['used'] ?? 0),
                'points_value' => (float) ($summary['applied']['points']['value'] ?? 0),
                'idempotency_key' => $summary['idempotency_key'] ?? null,
                'meta' => $summary['meta'] ?? null,
            ]);

            foreach ($cart->items as $ci) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $ci->service_id,
                    'quantity' => $ci->quantity,
                    'start_date' => $ci->start_date,
                    'end_date' => $ci->end_date,
                    'unit_price' => $ci->unit_price,
                    'tax' => $ci->tax,
                    'discount' => $ci->discount,
                    'line_total' => $ci->line_total,
                    'meta' => $ci->meta,
                ]);
            }

            // lock cart to prevent further edits during payment
            $cart->update(['status' => 'converted']);

            return $order;
        });
    }
}

