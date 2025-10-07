<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProviderRestaurantTablesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'user_type:provider', 'phone.verified']);
    }

    /**
     * عرض جميع طاولات مطعم مقدم الخدمة
     */
    public function index(Request $request, Restaurant $restaurant)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        try {
            $tables = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->with(['restaurant.service', 'media'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->integer('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => RestaurantTableResource::collection($tables),
                'pagination' => [
                    'current_page' => $tables->currentPage(),
                    'last_page' => $tables->lastPage(),
                    'per_page' => $tables->perPage(),
                    'total' => $tables->total(),
                ],
                'restaurant_info' => $this->getRestaurantInfo($restaurant),
                'provider_info' => $this->getProviderInfo($restaurant)
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
     * إنشاء طاولة جديدة
     */
    public function store(RestaurantTableRequest $request, Restaurant $restaurant)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        try {
            $data = $request->validated();
            $data['restaurant_id'] = $restaurant->id;

            $table = RestaurantTable::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Table created successfully',
                'data' => new RestaurantTableResource($table->load(['restaurant.service', 'media']))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض طاولة محددة
     */
    public function show(Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        try {
            $table->load(['restaurant.service', 'tableReservations.user', 'media']);

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
     * تحديث طاولة
     */
    public function update(RestaurantTableRequest $request, Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        try {
            $table->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully',
                'data' => new RestaurantTableResource($table->load(['restaurant.service', 'media']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف طاولة
     */
    public function destroy(Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        try {
            // حذف جميع الصور المرتبطة بالطاولة
            $table->clearMediaCollection('table_images');

            // حذف الطاولة
            $table->delete();

            return response()->json([
                'success' => true,
                'message' => 'Table deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفع صورة للطاولة
     */
    public function uploadImage(Request $request, Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // رفع الصورة
            $media = $table->addMediaFromRequest('image')
                ->toMediaCollection('table_images');

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail' => $media->getUrl('thumb'),
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف صورة من الطاولة
     */
    public function deleteImage(Restaurant $restaurant, RestaurantTable $table, $mediaId)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        try {
            $media = $table->getMedia('table_images')->where('id', $mediaId)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات الطاولات
     */
    public function statistics(Restaurant $restaurant)
    {
        // التحقق من أن المطعم ملك لمقدم الخدمة
        $this->authorizeRestaurant($restaurant);

        try {
            $totalTables = RestaurantTable::where('restaurant_id', $restaurant->id)->count();
            $normalTables = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->where('type', 'Normal')->count();
            $vipTables = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->where('type', 'VIP')->count();
            $totalCapacity = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->sum(DB::raw('capacity_people * quantity'));

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tables' => $totalTables,
                    'normal_tables' => $normalTables,
                    'vip_tables' => $vipTables,
                    'total_capacity' => $totalCapacity,
                    'average_capacity_per_table' => $totalTables > 0 ? round($totalCapacity / $totalTables, 2) : 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * التحقق من أن المطعم ملك لمقدم الخدمة
     */
    private function authorizeRestaurant(Restaurant $restaurant): void
    {
        if ((int)$restaurant->service->user_id !== (int)auth()->id()) {
            abort(403, 'Unauthorized access to this restaurant');
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

