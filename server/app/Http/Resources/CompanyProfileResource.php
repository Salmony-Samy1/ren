<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'commercial_record' => $this->commercial_record,
            'tax_number' => $this->tax_number,
            'description' => $this->description,
            'main_service_id' => $this->main_service_id,
            'owner' => $this->owner,
            'national_id' => $this->national_id,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'user_id' => $this->user_id,
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'city' => new CityResource($this->whenLoaded('city')),
        ];
    }
}
