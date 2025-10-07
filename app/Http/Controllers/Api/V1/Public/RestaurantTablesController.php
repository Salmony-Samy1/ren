<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantTableResource;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class RestaurantTablesController extends Controller
{
    /**
     * عرض جميع طاولات المطعم مع التفاصيل الكاملة
     * 
     * @param Restaurant $restaurant
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Restaurant $restaurant)
    {
        try {
            // جلب الطاولات مع العلاقات المطلوبة باستخدام Eager Loading
            $tables = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->with([
                    'restaurant.service',
                    'tableReservations.user',
                    'media'
                ])
                ->get();

            // استخدام API Resource لتنسيق البيانات
            $tablesResource = RestaurantTableResource::collection($tables);

            return response()->json([
                'success' => true,
                'data' => $tablesResource,
                'restaurant_info' => $this->getRestaurantInfo($restaurant),
                'provider_info' => $this->getProviderInfo($restaurant),
                'meta' => [
                    'total_tables' => $tables->count(),
                    'available_tables' => $tables->where('quantity', '>', 0)->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض طاولة محددة
     * 
     * @param Restaurant $restaurant
     * @param RestaurantTable $table
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Restaurant $restaurant, RestaurantTable $table)
    {
        try {
            // التحقق من أن الطاولة تنتمي للمطعم
            if ((int)$table->restaurant_id !== (int)$restaurant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found in this restaurant'
                ], 404);
            }

            // جلب الطاولة مع العلاقات
            $table->load([
                'restaurant.service',
                'tableReservations.user',
                'media'
            ]);

            return response()->json([
                'success' => true,
                'data' => new RestaurantTableResource($table),
                'restaurant_info' => $this->getRestaurantInfo($restaurant),
                'provider_info' => $this->getProviderInfo($restaurant)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve table details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب معلومات المطعم الكاملة
     */
    private function getRestaurantInfo($restaurant)
    {
        $service = $restaurant->service;
        
        return [
            'id' => $restaurant->id,
            'service_id' => $service->id,
            'name' => $service->name ?? 'Unknown Restaurant',
            'description' => $service->description ?? null,
            'address' => $service->address ?? null,
            'latitude' => $service->latitude ?? null,
            'longitude' => $service->longitude ?? null,
            'phone' => $service->phone ?? null,
            'email' => $service->email ?? null,
            'website' => $service->website ?? null,
            'city' => $service->city ?? null,
            'country' => $service->country ?? null,
            'postal_code' => $service->postal_code ?? null,
            'price_range' => $service->price_range ?? null,
            'cuisine_type' => $service->cuisine_type ?? null,
            'dress_code' => $service->dress_code ?? null,
            'parking_available' => $service->parking_available ?? false,
            'valet_parking' => $service->valet_parking ?? false,
            'wifi_available' => $service->wifi_available ?? false,
            'outdoor_seating' => $service->outdoor_seating ?? false,
            'smoking_area' => $service->smoking_area ?? false,
            'wheelchair_accessible' => $service->wheelchair_accessible ?? false,
            'live_music' => $service->live_music ?? false,
            'private_dining' => $service->private_dining ?? false,
            'delivery_available' => $service->delivery_available ?? false,
            'takeaway_available' => $service->takeaway_available ?? false,
            'reservation_required' => $service->reservation_required ?? false,
            'group_friendly' => $service->group_friendly ?? false,
            'family_friendly' => $service->family_friendly ?? false,
            'pet_friendly' => $service->pet_friendly ?? false,
            'currency' => $service->currency ?? null,
            'price_per_person_min' => $service->price_per_person_min ?? null,
            'price_per_person_max' => $service->price_per_person_max ?? null,
            'service_charge' => $service->service_charge ?? null,
            'tax_rate' => $service->tax_rate ?? null,
            'delivery_fee' => $service->delivery_fee ?? null,
            'minimum_order' => $service->minimum_order ?? null,
            'opening_hours' => $service->opening_hours ?? null,
            'closing_hours' => $service->closing_hours ?? null,
            'is_24_hours' => $service->is_24_hours ?? false,
            'status' => $service->status ?? null,
            'is_active' => $service->is_active ?? false,
            'is_featured' => $service->is_featured ?? false,
            'rating' => $service->rating ?? null,
            'review_count' => $service->review_count ?? 0,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
            'restaurant_details' => [
                'working_hours' => $restaurant->working_hours ?? null,
                'available_tables_map' => $restaurant->available_tables_map ?? null,
                'daily_available_bookings' => $restaurant->daily_available_bookings ?? null,
                'grace_period_minutes' => $restaurant->grace_period_minutes ?? null,
            ]
        ];
    }

    /**
     * جلب معلومات مقدم الخدمة
     */
    private function getProviderInfo($restaurant)
    {
        $provider = $restaurant->service->user;
        
        return [
            'id' => $provider->id,
            'name' => $provider->name ?? null,
            'email' => $provider->email ?? null,
            'phone' => $provider->phone ?? null,
            'avatar' => $provider->avatar ?? null,
            'type' => $provider->type ?? null,
            'is_verified' => $provider->is_verified ?? false,
            'phone_verified_at' => $provider->phone_verified_at ?? null,
            'email_verified_at' => $provider->email_verified_at ?? null,
            'created_at' => $provider->created_at,
            'updated_at' => $provider->updated_at,
            'profile' => [
                'bio' => $provider->bio ?? null,
                'location' => $provider->location ?? null,
                'website' => $provider->website ?? null,
                'social_links' => $provider->social_links ?? null,
            ]
        ];
    }
}

