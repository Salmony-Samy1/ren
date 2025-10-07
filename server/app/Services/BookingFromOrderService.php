<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingFromOrderService
{
    public function __construct(private readonly EscrowService $escrow)
    {
    }

    /** Create booking without charging again, attach tx and hold provider net in escrow. */
    public function createForItem(OrderItem $item, User $user, string $paymentMethod, ?string $couponCode, ?string $sharedTransactionId = null): Booking
    {
        return DB::transaction(function () use ($item, $user, $paymentMethod, $couponCode, $sharedTransactionId) {
            // Compute per-item share of points to ensure provider compensation from platform commission first
            $order = $item->order()->with('items')->first();
            $pointsValueShare = 0.0;
            if ($order && (float) $order->points_value > 0) {
                $itemsTotal = max(0.01, (float) $order->items->sum('line_total'));
                $pointsValueShare = round(((float) $item->line_total / $itemsTotal) * (float) $order->points_value, 2);
            }

            $rate = (float) get_setting('points_to_wallet_rate');
            $pointsUsed = ($rate > 0 && $pointsValueShare > 0) ? (int) floor($pointsValueShare / $rate) : 0;

            $booking = Booking::create([
                'user_id' => $user->id,
                'service_id' => $item->service_id,
                'start_date' => $item->start_date,
                'end_date' => $item->end_date,
                'booking_details' => [],
                'tax' => (float) $item->tax,
                'subtotal' => (float) $item->unit_price * (int) $item->quantity,
                'discount' => (float) $item->discount,
                'points_used' => $pointsUsed,
                'points_value' => $pointsValueShare,
                'total' => (float) $item->line_total,
                'payment_method' => $paymentMethod,
                'transaction_id' => $sharedTransactionId, // shared across items
                'status' => 'confirmed',
            ]);

            // Find corresponding item-level payment transaction
            $tx = PaymentTransaction::where('transaction_id', $sharedTransactionId ? ($sharedTransactionId.'-'.$item->id) : null)
                ->whereNull('booking_id')
                ->where('amount', (float) $item->line_total)
                ->where('status', 'completed')
                ->lockForUpdate()
                ->first();

            if ($tx) {
                $tx->update(['booking_id' => $booking->id]);
                $this->escrow->holdFundsNetForBooking($booking, $tx);
            }

            return $booking;
        });
    }
}

