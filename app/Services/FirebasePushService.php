<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebasePushService
{
    private ?\Kreait\Firebase\Messaging $messaging = null;

    public function __construct()
    {
        $path = env('FIREBASE_CREDENTIALS');
        if (! $path) {
            return; // not configured
        }
        try {
            $factory = (new Factory())->withServiceAccount($path);
            $this->messaging = $factory->createMessaging();
        } catch (\Throwable $e) {
            report($e);
            $this->messaging = null;
        }
    }

    public function isAvailable(): bool
    {
        return (bool) $this->messaging;
    }

    public function sendToUserTopic(int $userId, array $payload): void
    {
        if (! (bool) Config::get('services.fcm.enabled', false)) {
            return;
        }
        if (! $this->messaging) return;

        $topic = 'user-' . $userId;
        $message = CloudMessage::withTarget('topic', $topic)
            ->withData($payload);

        try {
            $this->messaging->send($message);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

