<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutSummaryRequest;
use App\Models\Cart;
use App\Services\CartPricingService;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartPricingService $pricing)
    {
        $this->middleware('auth:api');
    }

    public function summary(CheckoutSummaryRequest $request)
    {
        $user = auth()->user();
        $cart = Cart::with('items.service')->firstOrCreate(['user_id' => $user->id, 'status' => 'open']);
        $this->pricing->recomputeCart($cart);

        $subtotal = (float) $cart->subtotal;
        $tax = (float) $cart->tax_total;
        $discount = (float) $cart->discount_total;
        $grand = (float) $cart->grand_total;

        $applied = [
            'coupon' => null,
            'points' => ['used' => 0, 'value' => 0.0],
        ];

        $totalAfterDiscounts = $grand;

        // Apply coupon
        $couponCode = $request->input('coupon_code');
        if ($couponCode) {
            $couponService = app(\App\Services\CouponService::class);
            try {
                $coupon = $couponService->validateCoupon($couponCode, $user, $grand);
                $couponRes = $couponService->applyCoupon($coupon, $grand);
                $discount += (float) $couponRes['discount'];
                $totalAfterDiscounts = (float) $couponRes['total_after_discount'];
                $applied['coupon'] = ['code' => $coupon->code, 'discount' => (float) $couponRes['discount']];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        // Apply points with 50% cap
        $pointsRequested = (int) $request->input('points_to_use', 0);
        if ($pointsRequested > 0 && $totalAfterDiscounts > 0) {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $balance = $ledger->balance($user);
            $eligible = min($pointsRequested, $balance);

            $rate = (float) get_setting('points_to_wallet_rate');
            $maxRatio = (float) get_setting('points_max_redeem_ratio');
            if ($rate <= 0 || $maxRatio <= 0) {
                return response()->json(['success' => false, 'message' => 'Points settings not configured. Please set points_to_wallet_rate and points_max_redeem_ratio in admin.'], 422);
            }

            $capValue = max(0.0, round($totalAfterDiscounts * $maxRatio, 2));
            $eligibleValue = round($eligible * $rate, 2);
            $valueToApply = min($capValue, $eligibleValue);
            $pointsToApply = (int) floor($valueToApply / max($rate, 0.000001));

            if ($pointsToApply > 0) {
                // Summary endpoint does not mutate ledger; only preview
                $applied['points'] = [
                    'used' => $pointsToApply,
                    'value' => round($pointsToApply * $rate, 2),
                ];
                $totalAfterDiscounts = max(0.0, round($totalAfterDiscounts - $applied['points']['value'], 2));
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => [
                    'items' => $cart->items->map(fn($i) => [
                        'id' => $i->id,
                        'service_id' => $i->service_id,
                        'service_name' => $i->service->name ?? null,
                        'quantity' => $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                        'line_total' => (float) $i->line_total,
                    ]),
                    'totals' => [
                        'subtotal' => $subtotal,
                        'tax' => $tax,
                        'discount' => $discount,
                        'grand_total' => $grand,
                    ]
                ],
                'applied' => $applied,
                'payable_total' => $totalAfterDiscounts,
            ]
        ]);
    }
}

