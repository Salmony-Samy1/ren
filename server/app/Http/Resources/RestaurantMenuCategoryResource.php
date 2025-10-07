<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantMenuCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'is_active'     => (bool) $this->is_active,
            'display_order' => (int) $this->display_order,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}