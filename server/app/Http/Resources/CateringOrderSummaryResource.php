<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringOrderSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->reference_code ?? 'ORD-CTR-' . $this->id,
            'booking_id' => $this->id,
            'provider_id' => $this->service?->user_id,
            'provider_name' => $this->service?->user?->full_name,
            'customer_name' => $this->user?->full_name,
            'customer_phone' => $this->user?->phone,
            'customer_email' => $this->user?->email,
            'order_type' => $this->order_type ?? 'online',
            'status' => $this->status,
            'payment_status' => $this->payment_status ?? 'unpaid',
            'total_amount' => $this->total,
            'order_date' => $this->created_at?->toISOString(),
            'delivery_date' => $this->start_date?->toISOString(),
            'delivery_address' => [
                'street' => $this->booking_details['delivery_address']['street'] ?? null,
                'building' => $this->booking_details['delivery_address']['building'] ?? null,
                'district' => $this->booking_details['delivery_address']['district'] ?? null,
                'city' => $this->booking_details['delivery_address']['city'] ?? null,
                'lat' => $this->booking_details['delivery_address']['lat'] ?? null,
                'long' => $this->booking_details['delivery_address']['long'] ?? null,
            ],
            'items_count' => count($this->booking_details['items'] ?? []),
            'payment_method' => $this->payment_method,
            'created_by_admin' => false, // Will be updated when implementing admin order creation
            'admin_notes' => $this->booking_details['admin_notes'] ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}