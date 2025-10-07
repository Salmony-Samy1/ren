<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => asset($this->icon),
            'status' => (int)$this->status,
            'translations' => $this->getTranslationsArray(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'questions' => $this->whenLoaded('questions', function (){
                return QuestionResource::collection($this->questions);
            })
        ];
    }
}
