<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutConfirmRequest;
use App\Models\Cart;
use App\Services\CartPricingService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutConfirmController extends Controller
{
    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly OrderService $orders
    ) {
        $this->middleware('auth:api');
    }

    public function confirm(CheckoutConfirmRequest $request)
    {
        $user = auth()->user();
        $cart = Cart::with('items.service')->firstOrCreate(['user_id' => $user->id, 'status' => 'open']);
        $this->pricing->recomputeCart($cart);

        // Validate availability before charging
        $availability = app(\App\Services\CartAvailabilityService::class)->validate($cart);
        if (!($availability['valid'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'Availability check failed', 'errors' => $availability['errors']], 422);
        }

        // Reuse summary computation
        // Compute summary via service to avoid FormRequest type mismatch
        $summaryService = function($user) use ($request, $cart) {
            $controller = app(\App\Http\Controllers\Frontend\CheckoutController::class);
            // Bypass type hint by calling the core logic through a synthetic FormRequest
            $form = new \App\Http\Requests\Checkout\CheckoutSummaryRequest([], [
                'coupon_code' => $request->input('coupon_code'),
                'points_to_use' => $request->input('points_to_use'),
            ]);
            // Manually set user context
            auth()->setUser($user);
            return $controller->summary($form);
        };
        $summaryResponse = $summaryService($user);
        $payload = $summaryResponse->getData(true);
        if (!($payload['success'] ?? false)) {
            return response()->json($payload, 422);
        }
        $summary = $payload['data'];
        $summary['idempotency_key'] = $request->input('idempotency_key');

        // Idempotency: return existing order if same key
        $existing = \App\Models\Order::where('user_id', $user->id)
            ->where('idempotency_key', $summary['idempotency_key'])->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing->load('items')]);
        }

        // Spend points here (atomic) and create Order snapshot; Payment capture will follow
        return DB::transaction(function () use ($cart, $summary, $user, $request) {
            $pointsUsed = (int) ($summary['applied']['points']['used'] ?? 0);
            if ($pointsUsed > 0) {
                $ledger = app(\App\Services\PointsLedgerService::class);
                $ledger->spend($user, $pointsUsed, ['reason' => 'checkout']);
            }

            $order = $this->orders->createFromCart($cart->fresh('items'), $summary + ['meta' => ['source' => 'checkout']]);

            // Initiate payment capture (single payment for the order)
            $orderPayments = app(\App\Services\OrderPaymentService::class);
            $paymentResult = $orderPayments->chargeOnceForOrder($order, $user, $request->input('payment_method'));

            if (!($paymentResult['success'] ?? false)) {
                return response()->json(['success' => false, 'message' => $paymentResult['message'] ?? 'Payment failed'], 422);
            }

            // On success: create bookings for each order_item and attach payment tx
            $sharedTxId = $paymentResult['transaction_id'] ?? null;

            // Dispatch async fulfillment to improve UX and resilience
            \App\Jobs\FulfillOrderItemsJob::dispatch($order->id, $request->input('payment_method'), $summary['applied']['coupon']['code'] ?? null, $sharedTxId);

            return response()->json(['success' => true, 'data' => $order->load('items')]);
        });
    }
}

