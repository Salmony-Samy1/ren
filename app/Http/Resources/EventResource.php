<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        // Compute remaining spots for the event (based on overlapping bookings)
        $remaining = null;
        try {
            $cap = (int) ($this->max_individuals ?? 0);
            if ($cap > 0 && $this->start_at && $this->end_at) {
                $start = optional($this->start_at)->copy();
                $end = optional($this->end_at)->copy();
                $used = \App\Models\Booking::where('service_id', $this->service_id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($q) use ($start, $end) {
                                $q->where('start_date', '<=', $start)
                                  ->where('end_date', '>=', $end);
                            });
                    })
                    ->get()
                    ->sum(function ($b) { return (int)($b->booking_details['number_of_people'] ?? 1); });
                $remaining = max(0, $cap - (int)$used);
            }
        } catch (\Throwable $e) {
            $remaining = null;
        }

        return [
            'id' => $this->id,
            'service_id' => (int)$this->service_id,
            'event_name' => $this->event_name,
            'description' => $this->description,
            'language' => $this->language,
            'max_individuals' => $this->max_individuals,
            'start_at' => optional($this->start_at)->toDateTimeString(),
            'end_at' => optional($this->end_at)->toDateTimeString(),
            'gender_type' => $this->gender_type,
            'hospitality_available' => (bool)$this->hospitality_available,
            'price_per_person' => $this->price_per_person !== null ? (float)$this->price_per_person : null,
            'price_currency_id' => $this->price_currency_id !== null ? (int)$this->price_currency_id : null,
            'meeting_point' => $this->meeting_point,
            'age_min' => $this->age_min !== null ? (int)$this->age_min : null,
            'age_max' => $this->age_max !== null ? (int)$this->age_max : null,
            'images' => (function(){
                try {
                    $imgs = collect(is_array($this->images) ? $this->images : []);
                } catch (\Throwable $e) { $imgs = collect([]); }
                if ($imgs->isEmpty()) {
                    $imgs = $this->getMedia('event_images')->map(fn($m) => $m->getUrl())->values();
                }
                if ($imgs->isEmpty() && $this->relationLoaded('service')) {
                    try { $imgs = $this->service?->getMedia('images')->map(fn($m) => $m->getUrl())->values(); } catch (\Throwable $e) {}
                }
                return $imgs;
            })(),
            'videos' => (function(){
                try {
                    $vidsArr = is_array($this->videos) ? $this->videos : [];
                    $vids = collect($vidsArr);
                } catch (\Throwable $e) { $vids = collect([]); }
                if ($vids->isEmpty()) {
                    $vids = $this->getMedia('event_videos')->map(fn($m) => $m->getUrl())->values();
                }
                if ($vids->isEmpty() && $this->relationLoaded('service')) {
                    try { $vids = $this->service?->getMedia('videos')->map(fn($m) => $m->getUrl())->values(); } catch (\Throwable $e) {}
                }
                return $vids;
            })(),
            'remaining' => $remaining,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}

