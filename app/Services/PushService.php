<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PushService
{
    public function sendFcmToUserTopic(int $userId, array $payload): void
    {
        if (! (bool) Config::get('services.fcm.enabled', false)) {
            return;
        }
        $serverKey = (string) Config::get('services.fcm.server_key');
        if (! $serverKey) return;

        $topic = 'user-'.$userId;
        $body = [
            'to' => '/topics/'.$topic,
            'content_available' => true,
            'priority' => 'high',
            'data' => $payload,
        ];

        try {
            $res = Http::withToken($serverKey)
                ->acceptJson()
                ->asJson()
                ->post('https://fcm.googleapis.com/fcm/send', $body);
            if (! $res->successful()) {
                Log::warning('FCM push failed', ['user_id' => $userId, 'status' => $res->status(), 'body' => $res->body()]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

