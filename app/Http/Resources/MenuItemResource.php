<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'image_url' => $this->image_url, // This uses the accessor we created in the model
            'category' => [ // The category object is nested inside the item
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}