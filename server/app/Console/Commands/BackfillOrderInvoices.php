<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderInvoices extends Command
{
    protected $signature = 'invoices:backfill-order {--dry-run : Do not write, just show what would change} {--limit=500 : Max rows to process per run}';
    protected $description = 'Backfill legacy invoices/bookings to use single order-level invoices and reference codes. Idempotent.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Starting backfill (dry-run='.($dry?'yes':'no').', limit='.$limit.')');

        $count = 0;
        $bookings = Booking::with(['service','user','order','invoice'])
            ->whereNull('reference_code')
            ->orWhereNull('order_id')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($bookings as $booking) {
            DB::beginTransaction();
            try {
                // Ensure order exists
                $order = $booking->order;
                if (!$order) {
                    $order = Order::create([
                        'user_id' => $booking->user_id,
                        'status' => $booking->status === 'confirmed' ? 'paid' : 'pending',
                        'payment_status' => $booking->status === 'confirmed' ? 'completed' : 'pending',
                        'subtotal' => (float) $booking->subtotal,
                        'tax_total' => (float) $booking->tax,
                        'discount_total' => (float) $booking->discount,
                        'grand_total' => (float) $booking->total,
                        'payable_total' => (float) $booking->total,
                        'meta' => ['backfilled' => true, 'booking_id' => $booking->id],
                    ]);
                    if (!$dry) $order->save();

                    // Generate order_number
                    $seq = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
                    $datePart = now()->format('Ymd');
                    $orderNumber = 'ORD-' . $datePart . '-' . $seq;
                    if (!$dry) $order->update(['order_number' => $orderNumber]);

                    // Link booking
                    if (!$dry) $booking->update(['order_id' => $order->id, 'reference_code' => $orderNumber]);
                } else {
                    // Ensure reference_code exists
                    if (empty($booking->reference_code) && !empty($order->order_number)) {
                        if (!$dry) $booking->update(['reference_code' => $order->order_number]);
                    }
                }

                // Consolidate invoice: prefer single invoice per order
                $inv = Invoice::where('order_id', $order->id)->first();
                if (!$inv) {
                    // Try to reuse existing booking invoice
                    $inv = $booking->invoice;
                    if ($inv) {
                        if (!$dry) $inv->update(['order_id' => $order->id, 'booking_id' => null]);
                    } else {
                        // Create new consolidated invoice
                        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
                        $inv = new Invoice([
                            'user_id' => $booking->user_id,
                            'order_id' => $order->id,
                            'invoice_number' => $invoiceNumber,
                            'total_amount' => (float) $order->grand_total,
                            'tax_amount' => (float) $order->tax_total,
                            'discount_amount' => (float) $order->discount_total,
                            'commission_amount' => 0,
                            'provider_amount' => 0,
                            'platform_amount' => 0,
                            'status' => $order->status === 'paid' ? 'paid' : 'pending',
                        ]);
                        if (!$dry) $inv->save();
                    }
                }

                // Ensure invoice items exist based on order items
                if ($inv && $order->items()->count() > 0 && $inv->items()->count() === 0) {
                    foreach ($order->items as $oi) {
                        if ($dry) continue;
                        InvoiceItem::create([
                            'invoice_id' => $inv->id,
                            'description' => 'Service #' . $oi->service_id,
                            'quantity' => $oi->quantity,
                            'unit_price' => $oi->unit_price,
                            'total' => $oi->line_total,
                            'tax_rate' => 0,
                            'tax_amount' => $oi->tax,
                        ]);
                    }
                }

                if (!$dry) DB::commit(); else DB::rollBack();
                $count++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Error booking #'.$booking->id.': '.$e->getMessage());
            }
        }

        $this->info('Backfill processed bookings: '.$count);
        $this->info('Done.');
        return self::SUCCESS;
    }
}

