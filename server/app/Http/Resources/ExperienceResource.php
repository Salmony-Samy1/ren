<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'main_service_id' => $this->main_service_id,
            'caption' => $this->caption,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at->toDateTimeString(),
            'images' => $this->getMedia('experience_images')->map(fn ($media) => $media->getUrl()),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}