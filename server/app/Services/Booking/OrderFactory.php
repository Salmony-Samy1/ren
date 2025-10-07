<?php

namespace App\Services\Booking;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;

use App\Services\Booking\Contracts\IOrderFactory;

class OrderFactory implements IOrderFactory
{
    /**
     * Create an Order and its OrderItem for a single booking, then link booking to order.
     * Returns array: [Order $order, string $orderNumber, string $invoiceNumber]
     */
    public function createForSingle(Booking $booking, Service $service, User $user, array $fees, array $paymentResult, ?string $paymentMethod): array
    {
        $order = new Order([
            'user_id' => $user->id,
            'status' => ($paymentResult['success'] ?? false) ? 'paid' : 'pending',
            'payment_status' => ($paymentResult['success'] ?? false) ? 'completed' : 'pending',
            'subtotal' => (float) $fees['subtotal'],
            'tax_total' => (float) $fees['tax_amount'],
            'discount_total' => (float) $fees['discount'],
            'grand_total' => (float) $fees['total_amount'],
            'payable_total' => (float) $fees['total_amount'],
        ]);
        $order->save();

        // Generate unified reference numbers matching legacy format
        $seq = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
        $datePart = now()->format('Ymd');
        $orderNumber = 'ORD-' . $datePart . '-' . $seq;
        $invoiceNumber = 'INV-' . $datePart . '-' . $seq;
        $order->update(['order_number' => $orderNumber]);

        // Link booking to order and set reference
        $booking->update(['order_id' => $order->id, 'reference_code' => $orderNumber]);

        // Determine quantity by service type
        $oiQty = 1;
        if ($service->event) {
            $oiQty = (int) ($booking->booking_details['number_of_people'] ?? 1);
        } elseif ($service->catering) {
            $oiQty = (int) ($booking->booking_details['number_of_items'] ?? 1);
        }

        // Create OrderItem for this booking (preserve legacy fields)
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $booking->service_id,
            'quantity' => $oiQty,
            'start_date' => $booking->start_date,
            'end_date' => $booking->end_date,
            'unit_price' => (float) ($booking->service->price_amount ?? 0),
            'tax' => (float) ($booking->tax ?? 0),
            'discount' => (float) ($booking->discount ?? 0),
            'line_total' => (float) ($booking->total ?? 0),
            'meta' => [ 'add_ons' => $booking->booking_details['add_ons'] ?? [] ],
        ]);

        return [$order, $orderNumber, $invoiceNumber];
    }

    /**
     * Create a consolidated order for bulk bookings, link bookings, create order items.
     * Returns array: [Order $order, string $orderNumber, string $invoiceNumber]
     */
    public function createForBulk(User $user, array $createdBookings, float $subtotalTotal, float $taxTotal, float $discountTotal, float $grandTotal, array $payment, ?string $idempotencyKey = null, ?string $notes = null): array
    {
        $order = new Order([
            'user_id' => $user->id,
            'status' => ($payment['success'] ?? false) ? 'paid' : 'pending',
            'payment_status' => ($payment['success'] ?? false) ? 'completed' : 'pending',
            'subtotal' => $subtotalTotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'payable_total' => $grandTotal,
            'coupon_code' => null,
            'coupon_discount' => 0,
            'points_used' => 0,
            'points_value' => 0,
            'idempotency_key' => $idempotencyKey,
            'meta' => [
                'notes' => $notes,
                'payment_transaction_id' => $payment['transaction_id'] ?? null,
            ],
        ]);
        $order->save();

        // Generate unified reference numbers
        $seq = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
        $datePart = now()->format('Ymd');
        $orderNumber = 'ORD-' . $datePart . '-' . $seq;
        $invoiceNumber = 'INV-' . $datePart . '-' . $seq;
        $order->update(['order_number' => $orderNumber]);

        // Mark all bookings as confirmed and link; create OrderItems; assign reference_code
        foreach ($createdBookings as $row) {
            /** @var Booking $bk */
            $bk = $row['booking'];
            $bk->update([
                'status' => 'confirmed',
                'order_id' => $order->id,
                'reference_code' => $orderNumber,
            ]);
            event(new \App\Events\BookingStatusUpdated($bk));

            $details = $bk->booking_details ?? [];
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $bk->service_id,
                'quantity' => (int) ($details['number_of_items'] ?? 1),
                'start_date' => $bk->start_date,
                'end_date' => $bk->end_date,
                'unit_price' => (float) ($bk->service->price_amount ?? 0),
                'tax' => (float) ($bk->tax ?? 0),
                'discount' => (float) ($bk->discount ?? 0),
                'line_total' => (float) ($bk->total ?? 0),
                'meta' => [
                    'notes' => $details['notes'] ?? null,
                    'add_ons' => $details['add_ons'] ?? [],
                ],
            ]);
        }

        return [$order, $orderNumber, $invoiceNumber];
    }
}

