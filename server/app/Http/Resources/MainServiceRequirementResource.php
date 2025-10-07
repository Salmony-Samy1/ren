<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainServiceRequirementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type->value,
            'document_type_name' => $this->document_type->name,
            'document_type_label' => ucwords(str_replace('_', ' ', $this->document_type->value)),
            'is_required' => $this->is_required,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'main_service' => $this->whenLoaded('mainService', function () {
                return [
                    'id' => $this->mainService->id,
                    'name' => $this->mainService->name,
                    'name_en' => $this->mainService->name_en,
                ];
            }),
            
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name_ar' => $this->country->name_ar,
                    'name_en' => $this->country->name_en,
                    'code' => $this->country->code,
                    'iso_code' => $this->country->iso_code,
                ];
            }),
        ];
    }
}