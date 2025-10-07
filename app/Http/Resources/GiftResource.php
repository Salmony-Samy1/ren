<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GiftResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'message' => $this->message,
            'sender' => new UserMiniResource($this->whenLoaded('sender')),
            'recipient' => new UserMiniResource($this->whenLoaded('recipient')),
            'package' => $this->whenLoaded('package', function () {
                return [
                    'id' => $this->package->id,
                    'name' => $this->package->name,
                    'amount' => (float) $this->package->amount,
                    'image_url' => $this->package->image_url,
                ];
            }),
            'voucher' => $this->when(isset($this->service_id), function () {
                return [
                    'service_id' => $this->service_id,
                    'service_name' => optional($this->service)->name,
                ];
            }),
            'created_at' => $this->created_at,
            'accepted_at' => $this->accepted_at,
            'rejected_at' => $this->rejected_at,
        ];
    }
}

