<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringMinimumRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rule_id' => $this->rule_id,
            'provider_id' => $this->provider_id,
            'provider_name' => $this->provider?->full_name ?? 'النظام العام',
            'rule_name' => $this->rule_name,
            'region_name' => $this->region_name,
            'city' => $this->city,
            'zone_coordinates' => [
                'center_lat' => $this->center_lat,
                'center_long' => $this->center_long,
                'radius_km' => $this->radius_km,
            ],
            'min_order_value' => $this->min_order_value,
            'delivery_fee' => $this->delivery_fee,
            'free_delivery_threshold' => $this->free_delivery_threshold,
            'max_delivery_distance_km' => $this->max_delivery_distance_km,
            'operating_hours' => $this->operating_hours,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'special_conditions' => $this->special_conditions,
            'applied_orders_count' => $this->applied_orders_count,
            'total_revenue_impact' => $this->total_revenue_impact,
            'coverage_area_km2' => round($this->coverage_area, 2),
            'is_within_operating_hours' => $this->is_within_operating_hours,
            'is_general_rule' => is_null($this->provider_id),
            'created_by_admin' => !is_null($this->created_by_admin),
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}