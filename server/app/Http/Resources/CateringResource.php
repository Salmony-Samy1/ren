<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Normalize legacy JSON arrays (avoid cases like [ [] ])
        $imagesJson = is_array($this->images) ? $this->images : [];
        $imagesFlat = [];
        try { $imagesFlat = \Illuminate\Support\Arr::flatten($imagesJson); } catch (\Throwable $e) { $imagesFlat = $imagesJson; }
        $imagesFlat = array_values(array_filter($imagesFlat, fn($v) => is_string($v) && trim($v) !== ''));

        $images = [];
        $videos = [];
        try {
            // Prefer JSON images if present (legacy), else use media library
        // Derive remaining stock from bookings overlap if items are time-bound
        $remaining = null;
        try {
            $cap = (int) ($this->available_stock ?? 0);
            if ($cap > 0 && (int)($this->service_id ?? 0) > 0) {
                $now = now();
                $used = \App\Models\Booking::where('service_id', (int) $this->service_id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $now)
                    ->whereDate('end_date', '>=', $now)
                    ->get()
                    ->sum(function ($b) { return (int)($b->booking_details['number_of_items'] ?? 0); });
                $remaining = max(0, $cap - (int)$used);
            }
        } catch (\Throwable $e) { $remaining = null; }

            if (!empty($imagesFlat)) {
                $images = $imagesFlat;
            } else {
                $images = $this->getMedia('catering_images')->map(fn($m) => $m->getUrl())->values()->all();
            }
            // Fallback: aggregate images from items if main catering has none
            if (empty($images) && $this->relationLoaded('items')) {
                $images = collect($this->items)->flatMap(function($item){
                    try { return $item->getMedia('catering_images')->map(fn($m) => $m->getUrl()); } catch (\Throwable $e) { return collect(); }
                })->filter(fn($url) => is_string($url) && trim($url) !== '')->values()->all();
            }
            // If still empty, try parent service images (historical data)
            if (empty($images) && $this->relationLoaded('service')) {
                try { $images = $this->service?->getMedia('images')->map(fn($m) => $m->getUrl())->values()->all(); } catch (\Throwable $e) {}
            }
            if (empty($videos) && $this->relationLoaded('service')) {
                try { $videos = $this->service?->getMedia('videos')->map(fn($m) => $m->getUrl())->values()->all(); } catch (\Throwable $e) {}
            }
            // Videos from main catering
            $videos = $this->getMedia('catering_videos')->map(fn($m) => $m->getUrl())->values()->all();
            // Fallback videos from items
            if (empty($videos) && $this->relationLoaded('items')) {
                $videos = collect($this->items)->flatMap(function($item){
                    try { return $item->getMedia('catering_videos')->map(fn($m) => $m->getUrl()); } catch (\Throwable $e) { return collect(); }
                })->filter(fn($url) => is_string($url) && trim($url) !== '')->values()->all();
            }
        } catch (\Throwable $e) {}


        return [
            'id' => $this->id,
            'description' => $this->description,
            'available_stock' => $this->available_stock !== null ? (int)$this->available_stock : null,
            'remaining' => $remaining,
            'fulfillment_methods' => $this->fulfillment_methods,
            'images' => $images,
            'videos' => $videos,
            'items' => CateringItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

