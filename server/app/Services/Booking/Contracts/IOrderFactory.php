<?php

namespace App\Services\Booking\Contracts;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Order;

interface IOrderFactory
{
    /**
     * Create an Order and its OrderItem for a single booking, then link booking to order.
     * Returns array: [Order $order, string $orderNumber, string $invoiceNumber]
     */
    public function createForSingle(Booking $booking, Service $service, User $user, array $fees, array $paymentResult, ?string $paymentMethod): array;

    /**
     * Create a consolidated order for bulk bookings, link bookings, create order items.
     * Returns array: [Order $order, string $orderNumber, string $invoiceNumber]
     */
    public function createForBulk(User $user, array $createdBookings, float $subtotalTotal, float $taxTotal, float $discountTotal, float $grandTotal, array $payment, ?string $idempotencyKey = null, ?string $notes = null): array;
}

