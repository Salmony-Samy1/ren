<?php

namespace App\Listeners;

use App\Events\GiftAccepted;
use App\Events\GiftOffered;
use App\Events\GiftRejected;
use App\Services\NotificationService;

class GiftNotificationsListener
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function onOffered(GiftOffered $event): void
    {
        $gift = $event->gift;
        $this->notifications->created([
            'user_id' => $gift->recipient_id,
            'action' => 'gift_offer',
            'message' => 'تلقيت هدية جديدة. قم بقبولها أو رفضها.',
        ]);
    }

    public function onAccepted(GiftAccepted $event): void
    {
        $gift = $event->gift;
        $this->notifications->created([
            'user_id' => $gift->sender_id,
            'action' => 'gift_accepted',
            'message' => 'تم قبول هديتك.',
        ]);
        $this->notifications->created([
            'user_id' => $gift->recipient_id,
            'action' => 'wallet_funds_received',
            'message' => 'تم إضافة رصيد إلى محفظتك.',
        ]);
    }

    public function onRejected(GiftRejected $event): void
    {
        $gift = $event->gift;
        $this->notifications->created([
            'user_id' => $gift->sender_id,
            'action' => 'gift_rejected',
            'message' => 'تم رفض هديتك.',
        ]);
    }
}

