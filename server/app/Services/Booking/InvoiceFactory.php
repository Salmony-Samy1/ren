<?php

namespace App\Services\Booking;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Booking;

use App\Services\Booking\Contracts\IInvoiceFactory;

class InvoiceFactory implements IInvoiceFactory
{
    /**
     * Create invoice for a single booking (order-level invoice), mirroring legacy fields.
     */
    public function createForSingle(Booking $booking, array $fees, string $invoiceNumber, string $currency, ?string $paymentMethod, ?string $transactionId): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $booking->user_id,
            'order_id' => $booking->order_id,
            'invoice_number' => $invoiceNumber,
            'total_amount' => (float) $fees['total_amount'],
            'tax_amount' => (float) $fees['tax_amount'],
            'discount_amount' => (float) $fees['discount'],
            'commission_amount' => (float) ($fees['commission_data']['total_commission'] ?? 0),
            'provider_amount' => (float) ($fees['commission_data']['provider_amount'] ?? 0),
            'platform_amount' => (float) ($fees['commission_data']['platform_amount'] ?? 0),
            'currency' => $currency,
            'invoice_type' => 'customer',
            'status' => $transactionId ? 'paid' : 'pending',
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Booking for ' . ($booking->service->name ?? ('Service #' . $booking->service_id)),
            'quantity' => (int) ($booking->booking_details['number_of_items'] ?? 1),
            'unit_price' => (float) $fees['subtotal'],
            'total' => (float) $fees['subtotal'],
            'tax_rate' => (float) ($fees['tax_rate'] ?? 0),
            'tax_amount' => (float) $fees['tax_amount'],
        ]);

        return $invoice;
    }

    /**
     * Create consolidated invoice for bulk order, mirroring legacy fields.
     */
    public function createForBulk(int $userId, int $orderId, string $invoiceNumber, float $grandTotal, float $taxTotal, float $discountTotal, string $currency, string $status, ?string $paymentMethod, ?string $transactionId): Invoice
    {
        return Invoice::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'invoice_number' => $invoiceNumber,
            'total_amount' => $grandTotal,
            'tax_amount' => $taxTotal,
            'discount_amount' => $discountTotal,
            'commission_amount' => 0,
            'provider_amount' => 0,
            'platform_amount' => 0,
            'currency' => $currency,
            'invoice_type' => 'customer',
            'status' => $status,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);
    }
}

