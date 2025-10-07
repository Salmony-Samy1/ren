<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderReelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $provider = $this->whenLoaded('user', function () {
            return [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name ?? optional($this->user->companyProfile)->company_name,
                'avatar_url' => $this->user->getFirstMediaUrl('avatar'),
            ];
        });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'caption' => $this->caption,
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'main_service_id' => $this->main_service_id,
            'is_public' => $this->is_public,
            'provider' => $provider,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}