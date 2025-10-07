<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Service;

class CartPricingService
{
    public function snapshotItem(CartItem $item): void
    {
        $service = $item->service()->first(['id','price_amount']);
        $unit = (float) ($service->price_amount ?? 0);
        $qty = (int) ($item->quantity ?? 1);
        $item->unit_price = $unit;
        $item->tax = 0; // TODO: tax rules by category/region
        $item->discount = 0; // applied at checkout via coupons/points
        $item->line_total = round(($unit * $qty) + $item->tax - $item->discount, 2);
    }

    public function recomputeCart(Cart $cart): void
    {
        $subtotal = 0; $tax = 0; $discount = 0;
        foreach ($cart->items as $item) {
            $this->snapshotItem($item);
            $item->save();
            $subtotal += (float) $item->unit_price * (int) $item->quantity;
            $tax += (float) $item->tax;
            $discount += (float) $item->discount;
        }
        $cart->subtotal = round($subtotal, 2);
        $cart->tax_total = round($tax, 2);
        $cart->discount_total = round($discount, 2);
        $cart->grand_total = round($cart->subtotal + $cart->tax_total - $cart->discount_total, 2);
        $cart->save();
    }
}

