<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\BookingReminder;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reminderId)
    {
    }

    public function handle(NotificationService $notifications): void
    {
        $reminder = BookingReminder::with('booking')->find($this->reminderId);
        if (!$reminder || $reminder->sent_at) return;

        $booking = $reminder->booking;
        if (!$booking) return;

        // Notify customer
        $notifications->created([
            'user_id' => $booking->user_id,
            'action' => 'booking_reminder',
            'message' => __('notifications.booking.reminder_' . $reminder->type, [
                'booking' => $booking->id,
            ]),
        ]);

        $reminder->update(['sent_at' => now()]);
    }
}

