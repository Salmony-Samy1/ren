<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Events\AdminRealtimeAlert;

class BroadcastAdminSignals
{
    public function handleBookingCompleted(BookingCompleted $event): void
    {
        // Minimal payload for admin live feed
        event(new AdminRealtimeAlert('booking.created', [
            'booking_id' => $event->booking->id,
            'user_id' => $event->booking->user_id,
            'service_id' => $event->booking->service_id,
            'total' => $event->booking->total,
            'start_date' => $event->booking->start_date,
        ]));
    }
}

