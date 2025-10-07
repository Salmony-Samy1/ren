<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringSpecialEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->event_id,
            'provider_id' => $this->provider_id,
            'provider_name' => $this->provider?->full_name,
            'event_name' => $this->event_name,
            'event_type' => $this->event_type,
            'event_type_label' => $this->event_type_label,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'event_datetime' => $this->event_datetime?->toISOString(),
            'location' => [
                'venue_name' => $this->venue_name,
                'full_address' => $this->full_address,
                'city' => $this->event_city,
                'lat' => $this->event_lat,
                'long' => $this->event_long,
            ],
            'guest_count' => $this->guest_count,
            'estimated_budget' => $this->estimated_budget,
            'confirmed_budget' => $this->confirmed_budget,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'progress_percentage' => $this->progress_percentage,
            'planning_start_date' => $this->planning_start_date?->toISOString(),
            'preparation_days' => $this->preparation_days,
            'special_requirements' => $this->special_requirements,
            'menu_items' => $this->menu_items,
            'timeline' => $this->timeline,
            'contact_persons' => $this->contact_persons,
            'primary_contact' => $this->primary_contact,
            'admin_notes' => $this->admin_notes,
            'has_overdue_milestones' => $this->has_overdue_milestones,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}