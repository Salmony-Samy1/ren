<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'date' => $this->date,
            'gender' => $this->gender,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'neigbourhood' => new NeigbourhoodResource($this->neigbourhood),
            'images' => $this->images->map(fn($image) => asset($image->path))
        ];
    }
}
