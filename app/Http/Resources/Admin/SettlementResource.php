<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray($request)
    {
        $booking = $this->whenLoaded('booking');
        $service = $booking?->service;
        $provider = $service?->user;
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'settlement_status' => $this->settlement_status,
            'held_amount' => (float) $this->held_amount,
            'released_at' => $this->released_at,
            'refunded_at' => $this->refunded_at,
            'processed_by' => $this->processed_by,
            'admin_remarks' => $this->admin_remarks,
            'service' => $service ? [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $this->detectServiceType($service),
            ] : null,
            'provider' => $provider ? [
                'id' => $provider->id,
                'name' => $provider->name,
            ] : null,
            'payer' => [
                'id' => $this->user_id,
            ],
            'created_at' => $this->created_at,
        ];
    }

    private function detectServiceType($service): ?string
    {
        if ($service->event) return 'event';
        if ($service->restaurant) return 'restaurant';
        if ($service->property) return 'property';
        if ($service->cateringItem) return 'catering';
        return null;
    }
}

