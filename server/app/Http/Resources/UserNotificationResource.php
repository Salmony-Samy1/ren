<?php

namespace App\Http\Resources;

use App\Models\UserNotificaiton;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserNotificaiton */
class UserNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'action' => $this->action,
            'seen' => (int)$this->is_read,
            'date' => $this->created_at,
        ];
    }
}
