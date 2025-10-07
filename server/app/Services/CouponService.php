<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function validateCoupon(string $code, User $user, float $cartTotal): Coupon
    {
        $coupon = Coupon::where('code', $code)->firstOrFail();

        if ($coupon->status !== 'active') {
            abort(400, 'Coupon is inactive');
        }

        $now = now();
        if (($coupon->start_at && $now->lt($coupon->start_at)) || ($coupon->end_at && $now->gt($coupon->end_at))) {
            abort(400, 'Coupon not in valid time window');
        }

        if ($cartTotal < (float) $coupon->min_total) {
            abort(400, 'Cart total below minimum');
        }

        if ($coupon->max_uses !== null) {
            $totalUses = CouponRedemption::where('coupon_id', $coupon->id)->count();
            if ($totalUses >= $coupon->max_uses) {
                abort(400, 'Coupon usage limit reached');
            }
        }

        if ($coupon->per_user_limit !== null) {
            $userUses = CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
            if ($userUses >= $coupon->per_user_limit) {
                abort(400, 'You already used this coupon');
            }
        }

        return $coupon;
    }

    public function applyCoupon(Coupon $coupon, float $cartTotal): array
    {
        $discount = 0.0;
        if ($coupon->type === 'percent') {
            $discount = round($cartTotal * ((float)$coupon->amount / 100), 2);
        } else {
            $discount = (float) $coupon->amount;
        }
        $discount = min($discount, $cartTotal);

        return [
            'discount' => $discount,
            'total_after_discount' => round($cartTotal - $discount, 2),
        ];
    }

    public function redeem(User $user, Coupon $coupon, ?Booking $booking = null): void
    {
        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'booking_id' => $booking?->id,
            'used_at' => now(),
        ]);
    }
}

