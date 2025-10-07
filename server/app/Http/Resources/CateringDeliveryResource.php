<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'delivery_id' => $this->delivery_id,
            'order_id' => $this->booking?->reference_code ?? 'ORD-CTR-' . $this->booking_id,
            'provider_id' => $this->provider_id,
            'provider_name' => $this->provider?->full_name,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'delivery_datetime' => $this->scheduled_delivery_at?->toISOString(),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'address' => [
                'full_address' => $this->full_delivery_address,
                'street' => $this->delivery_street,
                'building' => $this->delivery_building,
                'district' => $this->delivery_district,
                'city' => $this->delivery_city,
                'lat' => $this->delivery_lat,
                'long' => $this->delivery_long,
            ],
            'total_amount' => $this->booking?->total ?? 0,
            'delivery_fee' => $this->delivery_fee,
            'free_delivery_applied' => $this->free_delivery_applied,
            'delivery_notes' => $this->delivery_notes,
            'admin_notes' => $this->admin_notes,
            'estimated_duration' => $this->estimated_duration_minutes ? $this->estimated_duration_minutes . ' minutes' : null,
            'driver_info' => [
                'driver_id' => $this->driver_id,
                'driver_name' => $this->driver_name,
                'driver_phone' => $this->driver_phone,
                'vehicle_plate' => $this->vehicle_plate,
            ],
            'actual_delivery_time' => $this->actual_delivery_at?->toISOString(),
            'delivery_person' => $this->delivery_person_name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}