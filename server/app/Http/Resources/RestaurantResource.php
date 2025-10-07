<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tables = $this->relationLoaded('tables') ? $this->tables : null;

        // Media collections mapping (kept internal names; exposed as unified keys)
        $photos = method_exists($this, 'getMedia')
            ? $this->getMedia('restaurant_images')->map(fn($m) => $m->getUrl())->values()->all()
            : [];
        $videos = method_exists($this, 'getMedia')
            ? $this->getMedia('restaurant_videos')->map(fn($m) => $m->getUrl())->values()->all()
            : [];

        // Provider (service owner) via UserResource
        $provider = null;
        if ($this->relationLoaded('service') && optional($this->service)->relationLoaded('user')) {
            $provider = new UserResource($this->service->user);
        } elseif ($this->relationLoaded('service') && $this->service && $this->service->user) {
            $provider = new UserResource($this->service->user);
        }

        // Stats
        $avgRating = optional($this->service)->rating_avg ?? null;
        $followersCount = optional(optional($this->service)->user)?->followers()->count();

        // Menu grouped by categories
        $menu = null;
        if ($this->relationLoaded('menuItems')) {
            $grouped = $this->menuItems->groupBy('restaurant_menu_category_id');
            $menu = $grouped->map(function ($items, $categoryId) {
                $category = optional($items->first())->category;
                return [
                    'id' => $category?->id ?? (int) $categoryId,
                    'name' => $category?->name ?? 'Uncategorized',
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'price' => $item->price,
                            'image_url' => $item->image_url,
                            'category_id' => $item->restaurant_menu_category_id,
                        ];
                    })->values(),
                ];
            })->values();
        }

        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'images' => $this->images,
            'daily_available_bookings' => $this->daily_available_bookings,
            'total_tables' => $this->total_tables,
            'description' => $this->description,
            'working_hours' => $this->working_hours,
            'available_tables_map' => $this->available_tables_map,

            // New enriched blocks (backward-compatible additions)
            'provider' => $provider,
            'media' => [
                'restaurant_photos' => $photos,
                'videos' => $videos,
            ],
            'stats' => [
                'average_rating' => $avgRating !== null ? round((float)$avgRating, 1) : null,
                'followers_count' => (int) ($followersCount ?? 0),
            ],
            'tables' => $tables ? $tables->map(function($t){
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'type' => $t->type,
                    'capacity_people' => $t->capacity_people,
                    'price_per_person' => $t->price_per_person,
                    'price_per_table' => $t->price_per_table,
                    'quantity' => $t->quantity,
                    're_availability_type' => $t->re_availability_type,
                    'auto_re_availability_minutes' => $t->auto_re_availability_minutes,
                    'conditions' => $t->conditions,
                    'amenities' => $t->amenities,
                    'media' => $t->media,
                    'created_at' => optional($t->created_at)->toDateTimeString(),
                    'updated_at' => optional($t->updated_at)->toDateTimeString(),
                ];
            })->values() : null,
            'menu' => $menu,
        ];
    }
}

