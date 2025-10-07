<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Attempt to fetch media if available; fall back gracefully
        $image = null; $video = null;
        try { $image = $this->getFirstMediaUrl('catering_item_photos'); } catch (\Throwable $e) { $image = null; }
        try { $video = $this->getFirstMediaUrl('catering_item_videos'); } catch (\Throwable $e) { $video = null; }

        // Compute remaining for add-on (per item) if stock policy is enabled
        $remaining = null;
        try {
            $cap = (int) ($this->available_stock ?? 0);
            if ($cap > 0 && (int)($this->catering_id ?? 0) > 0) {
                $now = now();
                $used = \App\Models\Booking::where('service_id', (int) $this->service_id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $now)
                    ->whereDate('end_date', '>=', $now)
                    ->get()
                    ->sum(function ($b) {
                        $arr = $b->booking_details['add_ons'] ?? [];
                        if (!is_array($arr)) return 0;
                        $sum = 0;
                        foreach ($arr as $x) { if ((int)($x['id'] ?? 0) === (int)$this->id) { $sum += (int)($x['qty'] ?? 0); } }
                        return $sum;
                    });
                $remaining = max(0, $cap - (int)$used);
            }
        } catch (\Throwable $e) { $remaining = null; }


        return [
            'id' => $this->id,
            // Treat CateringItem as an add-on: present a simple name/price payload
            'name' => $this->meal_name ?? $this->name ?? null,
            'price' => $this->price !== null ? (float)$this->price : null,
            'description' => $this->description,
            'availability_schedule' => $this->availability_schedule,
            'available_stock' => $this->available_stock !== null ? (int)$this->available_stock : null,
            'remaining' => $remaining,
            'category_id' => $this->category_id,
            'image' => $image,
            'video' => $video,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}

