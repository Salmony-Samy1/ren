<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BookingFromOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FulfillOrderItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 10; // seconds

    public function __construct(public readonly int $orderId, public readonly string $paymentMethod, public readonly ?string $couponCode, public readonly ?string $transactionId)
    {
    }

    public function handle(BookingFromOrderService $bookingFromOrder): void
    {
        $order = Order::with('items')->find($this->orderId);
        if (!$order) { return; }

        foreach ($order->items as $item) {
            if ($item->fulfillment_status === 'fulfilled') { continue; }
            try {
                $booking = $bookingFromOrder->createForItem($item, $order->user, $this->paymentMethod, $this->couponCode, $this->transactionId);
                $item->booking_id = $booking->id;
                $item->fulfillment_status = 'fulfilled';
                $item->error_message = null;
                $item->save();
                Log::info('Order item fulfilled', [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'booking_id' => $booking->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Order item fulfillment failed', [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                $item->fulfillment_status = 'failed';
                $item->error_message = $e->getMessage();
                $item->save();
                // rethrow to allow retry/backoff
                throw $e;
            }
        }
    }
}

