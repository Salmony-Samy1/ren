<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class MainServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name, 

            'translations' => [
                'ar' => [
                    'name' => $this->name,
                    'description' => $this->description,
                ],
                'en' => [
                    'name' => $this->name_en,
                    'description' => $this->description_en,
                ],
            ],

            'image' => $this->getFirstMediaUrl('service_image'),
            'video' => $this->getFirstMediaUrl('service_video'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];

    }
}