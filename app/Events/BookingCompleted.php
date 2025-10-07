<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function broadcastOn(): array
    {
        return [
            // Customer channel
            new PrivateChannel('Bookings.User.' . $this->booking->user_id),
            // Provider channel for the service owner
            new PrivateChannel('Services.' . $this->booking->service_id . '.Bookings'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'service_id' => $this->booking->service_id,
            'status' => $this->booking->status,
            'start_date' => $this->booking->start_date,
            'end_date' => $this->booking->end_date,
            'total' => $this->booking->total,
        ];
    }
}

