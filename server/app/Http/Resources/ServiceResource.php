<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\CarbonPeriod;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = strtolower($request->header('Accept-Language', 'ar')) === 'en' ? 'en' : 'ar';

        // Always ensure key relations are loaded for consistent payload
        $this->resource->loadMissing([
            'category',
            'user',
            'country',
            'currency',
            'property.bedrooms',
            'property.kitchens',
            'property.pools',
            'property.bathrooms',
            'property.livingRooms',
            'property.facilities',
            'event',
            'restaurant',
            'restaurant.tables',
            'catering.items'
        ]);

        // name may be stored as string; pass through as-is. If we later store translations JSON, pick per lang.
        $name = $this->name;
        if (is_array($this->name) && isset($this->name[$lang])) {
            $name = $this->name[$lang];
        }

        // Cover image (prefer service images; fallback to event media)
        $cover = null;
        try {
            $media = $this->getFirstMedia('images');
            if ($media) {
                $cover = [
                    'url' => $media->getUrl(),
                    'thumb' => method_exists($media, 'getUrl') ? $media->getUrl('thumb') : $media->getUrl(),
                ];
            }
        } catch (\Throwable $e) { $cover = null; }

        if (!$cover && $this->event) {
            try {
                $evMedia = $this->event->getFirstMedia('event_images');
                if ($evMedia) {
                    $cover = [
                        'url' => $evMedia->getUrl(),
                        'thumb' => method_exists($evMedia, 'getUrl') ? $evMedia->getUrl('thumb') : $evMedia->getUrl(),
                    ];
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
        if (!$cover && $this->catering) {
            try {
                $catMedia = $this->catering->getFirstMedia('catering_images');
                if ($catMedia) {
                    $cover = [
                        'url' => $catMedia->getUrl(),
                        'thumb' => method_exists($catMedia, 'getUrl') ? $catMedia->getUrl('thumb') : $catMedia->getUrl(),
                    ];
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Media arrays
        $serviceImages = [];
        try {
            $serviceImages = $this->getMedia('images')->map(fn($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'collection' => $m->collection_name,
                'name' => $m->file_name,
                'size' => $m->size,
            ])->values()->toArray();
        } catch (\Throwable $e) { $serviceImages = []; }

        $propertyImages = [];
        $propertyVideos = [];
        if ($this->property) {
            try {
                $propertyImages = $this->property->getMedia('property_images')->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'collection' => $m->collection_name,
                    'name' => $m->file_name,
                    'size' => $m->size,
                ])->values()->toArray();
                $propertyVideos = $this->property->getMedia('property_videos')->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'collection' => $m->collection_name,
                    'name' => $m->file_name,
                    'size' => $m->size,
                ])->values()->toArray();
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Prepare booked users and favorited users
        $bookedUsers = null;
        $bookedDates = []; 

        if ($this->relationLoaded('bookings')) {
            $bookedUsers = $this->bookings->loadMissing('user')->pluck('user')->filter()->unique('id')->values();
            $bookedUsers->each(function($u){ $u->loadMissing('customerProfile','companyProfile'); });
            $bookedDates = $this->bookings->flatMap(function ($booking) {
            $period = CarbonPeriod::create($booking->start_date, $booking->end_date);
                return collect($period)->map(fn($date) => $date->format('Y-m-d'))->all();
            })
            ->unique()
            ->values()
            ->all();

        
        }

        $favoritedUsers = null;
        if ($this->relationLoaded('favorites')) {
            $favoritedUsers = $this->favorites->loadMissing('user')->pluck('user')->filter()->unique('id')->values();
            $favoritedUsers->each(function($u){ $u->loadMissing('customerProfile','companyProfile'); });
        }

        $bookedUsersPayload = $bookedUsers ? $bookedUsers->map(fn($u) => ['user' => new UserResource($u)])->values() : null;
        $favoritedUsersPayload = $favoritedUsers ? $favoritedUsers->map(fn($u) => ['user' => new UserResource($u)])->values() : null;

        return [
            'id' => $this->id,
            'name' => $name,
            'category' => $this->category ? new CategoryResource($this->category) : null,
            'user' => $this->user ? new UserResource($this->user) : null,
            'address' => $this->address ?? optional($this->restaurant)->address ?? optional($this->property)->address ?? optional($this->event)->meeting_point,
            'latitude' => $this->latitude !== null ? (float)$this->latitude : null,
            'longitude' => $this->longitude !== null ? (float)$this->longitude : null,
            'place_id' => $this->place_id,
            'country_code' => $this->country_code,
            'country_id' => $this->country_id,
            'price_currency' => $this->price_currency ?? optional($this->currency)->code ?? null,
            'price_amount' => $this->price_amount !== null ? (float)$this->price_amount : null,
            'country' => $this->when($this->country, [
                'id' => optional($this->country)->id,
                'code' => optional($this->country)->code,
                'name_ar' => optional($this->country)->name_ar,
                'name_en' => optional($this->country)->name_en,
                'flag_emoji' => optional($this->country)->flag_emoji,
            ]),
            'currency' => $this->when($this->currency && $this->currency->id, [
                'id' => optional($this->currency)->id,
                'code' => optional($this->currency)->code,
                'name' => optional($this->currency)->name,
                'rate' => optional($this->currency)->rate,
                'fee_percent' => optional($this->currency)->fee_percent,
            ]),
            'images' => $serviceImages,
            'cover_image' => $cover,
            'is_approved' => (bool)$this->is_approved,
            'approved_at' => $this->approved_at,
            'updated_at' => optional($this->updated_at)->toDateTimeString(),

            // Always include relations
            'event' => $this->event ? new EventResource($this->event) : null,
            'property' => $this->property ? array_merge($this->property->toArray(), [
                'media' => [
                    'images' => $propertyImages,
                    'videos' => $propertyVideos,
                ],
                'booked_dates' => $bookedDates,
            ]) : null,
            'restaurant' => $this->restaurant ? new RestaurantResource($this->restaurant) : null,
            'catering' => $this->catering ? new CateringResource($this->catering->loadMissing('items')) : null,
            'catering_item' => null,

            // Optional counts when include_counts=1 is passed
            'favorites_count' => $this->when(filter_var($request->query('include_counts'), FILTER_VALIDATE_BOOLEAN), function () {
                if ($this->relationLoaded('favorites')) { return $this->favorites->count(); }
                return \App\Models\Favorite::where('service_id', $this->id)->count();
            }),
            'bookings_count' => $this->when(filter_var($request->query('include_counts'), FILTER_VALIDATE_BOOLEAN), function () {
                if ($this->relationLoaded('bookings')) { return $this->bookings->whereIn('status', ['confirmed','completed'])->count(); }
                return \App\Models\Booking::where('service_id', $this->id)->whereIn('status', ['confirmed','completed'])->count();
            }),
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'is_booking' => $this->is_booking,
            'is_favorited' => $this->relationLoaded('favorites') && auth()->check()
                ? $this->favorites->contains('user_id', auth()->id())
                : (bool) ($this->is_favorited ?? false),
            'booked_by_users' => $bookedUsersPayload,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'favorites' => $favoritedUsersPayload,
        ];
    }
}
