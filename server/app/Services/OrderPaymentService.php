<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Perform a single payment for the order.grand_total/payable_total and fan-out transactions per order item.
     */
    public function chargeOnceForOrder(Order $order, User $user, string $paymentMethod): array
    {
        return DB::transaction(function () use ($order, $user, $paymentMethod) {
            // One payment for the order
            $result = $this->payments->charge([
                'payment_method' => $paymentMethod,
                'amount' => (float) $order->payable_total,
            ], $user);

            if (!($result['success'] ?? false)) {
                return $result;
            }

            // Create item-level transactions (same gateway reference)
            $parentTxnId = $result['transaction_id'] ?? null;
            foreach ($order->items as $item) {
                $childTxnId = $parentTxnId ? ($parentTxnId . '-' . $item->id) : null;
                PaymentTransaction::create([
                    'user_id' => $user->id,
                    'booking_id' => null, // will be set after booking creation
                    'amount' => (float) $item->line_total,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                    'settlement_status' => 'held',
                    'held_amount' => (float) $item->line_total,
                    'transaction_id' => $childTxnId,
                    'gateway_response' => array_merge((array)($result['gateway_response'] ?? []), [
                        'parent_transaction_id' => $parentTxnId,
                        'order_item_id' => $item->id,
                    ]),
                ]);
            }

            $order->update([
                'status' => 'paid',
                'payment_status' => 'completed',
            ]);

            return $result;
        });
    }
}

