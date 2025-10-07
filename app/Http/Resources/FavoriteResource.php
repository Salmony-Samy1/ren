<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id, // ID of the favorite record itself
            'favorited_at' => $this->created_at->toDateTimeString(),
            'user'         => new UserResource($this->whenLoaded('user')),
        ];
    }
}