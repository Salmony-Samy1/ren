<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function broadcastOn(): array
    {
        return [
            // قناة العميل
            new PrivateChannel('Bookings.User.' . $this->booking->user_id),
            // قناة مزود الخدمة حسب الخدمة
            new PrivateChannel('Services.' . $this->booking->service_id . '.Bookings'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'status' => $this->booking->status,
                'start' => optional($this->booking->start_date)->toIso8601String(),
                'end' => optional($this->booking->end_date)->toIso8601String(),
                'service_id' => $this->booking->service_id,
                'user_id' => $this->booking->user_id,
                'updated_at' => $this->booking->updated_at?->toIso8601String(),
            ],
        ];
    }
}

