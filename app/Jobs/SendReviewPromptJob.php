<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReviewPromptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bookingId)
    {
    }

    public function handle(NotificationService $notifications): void
    {
        $booking = Booking::with('service:id,name')->find($this->bookingId);
        if (!$booking) return;

        $notifications->created([
            'user_id' => $booking->user_id,
            'action' => 'review_prompt',
            'message' => 'شاركنا رأيك في الخدمة التي حجزتها: ' . ($booking->service->name ?? ''),
            'deep_link' => 'app://reviews/new?service_id=' . $booking->service_id . '&booking_id=' . $booking->id,
        ]);
    }
}

