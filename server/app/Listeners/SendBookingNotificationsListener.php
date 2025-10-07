<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Events\BookingCancelled;
use App\Events\BookingStatusUpdated;
use App\Services\EscrowService;
use App\Services\NotificationService;
use App\Models\PaymentTransaction;

class SendBookingNotificationsListener
{
    public function __construct(private readonly NotificationService $notificationService, private readonly EscrowService $escrowService)
    {
    }

    public function handleBookingCompleted(BookingCompleted $event): void
    {
        $booking = $event->booking;
        $service = $booking->service()->with('user')->first();

        $this->notificationService->created([
            'user_id' => $booking->user_id,
            'action' => 'booking_confirmed',
            'message' => __('notifications.booking.confirmed'),
        ]);

        if ($service) {
            $this->notificationService->created([
                'user_id' => $service->user_id,
                'action' => 'new_booking',
                'message' => __('notifications.booking.new', ['service' => $service->name]),
            ]);
        }

        // If manual admin approval is enabled, notify admins of pending payout
        if (get_setting('provider_payout_trigger', 'manual_admin_approval') === 'manual_admin_approval') {
            foreach (\App\Models\User::where('type', 'admin')->pluck('id') as $adminId) {
                $this->notificationService->created([
                    'user_id' => $adminId,
                    'action' => 'payout_pending',
                    'message' => 'Payout for Booking #' . $booking->id . ' is awaiting your review',
                ]);
            }
        }

        // Automatic payout on completion if configured
        if (get_setting('provider_payout_trigger', 'manual_admin_approval') === 'automatic_on_completion') {
            $heldTxs = PaymentTransaction::where('booking_id', $booking->id)
                ->where('status', 'completed')
                ->where('settlement_status', 'held')
                ->lockForUpdate()->get();
            foreach ($heldTxs as $tx) {
                $this->escrowService->releaseToProvider($tx);
                $providerId = optional($booking->service)->user_id;
                if ($providerId) {
                    $this->notificationService->created([
                        'user_id' => $providerId,
                        'action' => 'payout_released',
                        'message' => __('notifications.payout.released', ['booking' => $booking->id]),
                    ]);
                }
            }
        }

        $delayMinutes = (int) get_setting('review_prompt_delay_minutes', 120);
        \App\Jobs\SendReviewPromptJob::dispatch($booking->id)->delay(now()->addMinutes($delayMinutes));
    }

    public function handleBookingCancelled(BookingCancelled $event): void
    {
        $booking = $event->booking;

        $this->notificationService->created([
            'user_id' => $booking->user_id,
            'action' => 'booking_cancelled',
            'message' => __('notifications.booking.cancelled', ['reason' => $event->reason ?? __('common.unknown_reason')]),
        ]);
    }

    public function handleBookingStatusUpdated(BookingStatusUpdated $event): void
    {
        $booking = $event->booking;
        $this->notificationService->created([
            'user_id' => $booking->user_id,
            'action' => 'booking_status_updated',
            'message' => 'تم تحديث حالة حجزك إلى: ' . $booking->status,
        ]);
    }
}

