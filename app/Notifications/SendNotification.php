<?php

namespace App\Notifications;

use App\Services\NotificationService;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SendNotification extends Notification
{
    public function __construct(public $data)
    {
    }

    public function via($notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->data);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
