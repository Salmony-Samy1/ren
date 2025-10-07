<?php

namespace App\Services\Booking\Contracts;

use App\Models\Invoice;
use App\Models\Booking;

interface IInvoiceFactory
{
    /**
     * Create invoice for a single booking (order-level invoice), mirroring legacy fields.
     */
    public function createForSingle(Booking $booking, array $fees, string $invoiceNumber, string $currency, ?string $paymentMethod, ?string $transactionId): Invoice;

    /**
     * Create consolidated invoice for bulk order, mirroring legacy fields.
     */
    public function createForBulk(int $userId, int $orderId, string $invoiceNumber, float $grandTotal, float $taxTotal, float $discountTotal, string $currency, string $status, ?string $paymentMethod, ?string $transactionId): Invoice;
}

