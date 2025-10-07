<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Booking;
use App\Services\Contracts\IRestaurantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RestaurantTablesController extends Controller
{
    public function __construct(private readonly IRestaurantService $restaurantService)
    {
        // إزالة middleware للوصول العام للطاولات
    }

    /**
     * عرض جميع طاولات المطعم مع التفاصيل الكاملة
     */
    public function index(Restaurant $restaurant)
    {
        try {
            // جلب الطاولات مع العلاقات المطلوبة
            $tables = RestaurantTable::where('restaurant_id', $restaurant->id)
                ->with(['restaurant.service'])
                ->get()
                ->map(function ($table) {
                    return $this->formatTableData($table);
                });

            return response()->json([
                'success' => true,
                'data' => $tables,
                'restaurant_info' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->service->name ?? 'Unknown Restaurant',
                    'description' => $restaurant->service->description ?? null,
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
     * تنسيق بيانات الطاولة مع جميع التفاصيل المطلوبة
     */
    private function formatTableData(RestaurantTable $table)
    {
        // جلب الصور من جدول media
        $images = $this->getTableImages($table);
        
        // جلب الأوقات المحجوزة
        $bookedTimes = $this->getBookedTimes($table);
        
        // تنسيق البيانات
        return [
            'id' => $table->id,
            'name' => $table->name,
            'type' => $table->type,
            'capacity_people' => $table->capacity_people,
            'price_per_person' => $table->price_per_person,
            'price_per_table' => $table->price_per_table,
            'quantity' => $table->quantity,
            're_availability_type' => $table->re_availability_type,
            'auto_re_availability_minutes' => $table->auto_re_availability_minutes,
            
            // الصور
            'images' => $images,
            
            // شروط الحجز
            'booking_conditions' => $this->formatBookingConditions($table->conditions ?? []),
            
            // المميزات
            'amenities' => $this->formatAmenities($table->amenities ?? []),
            
            // الأوقات المحجوزة
            'booked_times' => $bookedTimes,
            
            // معلومات إضافية
            'created_at' => $table->created_at,
            'updated_at' => $table->updated_at,
        ];
    }

    /**
     * جلب صور الطاولة من جدول media
     */
    private function getTableImages(RestaurantTable $table)
    {
        try {
            // البحث عن الصور المرتبطة بالطاولة
            $media = Media::where('model_type', RestaurantTable::class)
                ->where('model_id', $table->id)
                ->where('collection_name', 'table_images')
                ->get()
                ->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'thumbnail' => $media->getUrl('thumb'),
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                    ];
                });

            return $media->toArray();
        } catch (\Exception $e) {
            // في حالة عدم وجود media library أو خطأ
            return [];
        }
    }

    /**
     * تنسيق شروط الحجز
     */
    private function formatBookingConditions(array $conditions)
    {
        $defaultConditions = [
            'minimum_advance_booking' => '2 hours', // الحد الأدنى للحجز المسبق
            'maximum_advance_booking' => '30 days', // الحد الأقصى للحجز المسبق
            'cancellation_policy' => 'Free cancellation up to 24 hours before',
            'deposit_required' => false,
            'deposit_amount' => 0,
            'age_restrictions' => null,
            'dress_code' => 'Smart casual',
            'special_requirements' => [],
        ];

        return array_merge($defaultConditions, $conditions);
    }

    /**
     * تنسيق المميزات
     */
    private function formatAmenities(array $amenities)
    {
        $defaultAmenities = [
            'wifi' => false,
            'air_conditioning' => true,
            'smoking_area' => false,
            'outdoor_seating' => false,
            'wheelchair_accessible' => false,
            'private_dining' => false,
            'live_music' => false,
            'parking' => false,
            'valet_parking' => false,
        ];

        return array_merge($defaultAmenities, $amenities);
    }

    /**
     * جلب الأوقات المحجوزة للطاولة
     */
    private function getBookedTimes(RestaurantTable $table)
    {
        try {
            $bookings = Booking::where('service_id', $table->restaurant->service_id)
                ->where('status', '!=', 'cancelled')
                ->where('booking_details->table_id', $table->id)
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->get()
                ->map(function ($booking) {
                    return [
                        'booking_id' => $booking->id,
                        'start_date' => $booking->start_date,
                        'end_date' => $booking->end_date,
                        'status' => $booking->status,
                        'customer_name' => $booking->user->name ?? 'Unknown',
                        'number_of_people' => $booking->booking_details['number_of_people'] ?? 1,
                    ];
                });

            return $bookings->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * رفع صورة للطاولة
     */
    public function uploadImage(Request $request, Restaurant $restaurant, RestaurantTable $table)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

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

    public function store(Request $request, Restaurant $restaurant)
    {
        $data = $request->validate([
            'name' => 'sometimes|nullable|string|max:120',
            'type' => 'required|in:Normal,VIP',
            'capacity_people' => 'required|integer|min:1',
            'price_per_person' => 'required_if:type,Normal|nullable|numeric|min:0',
            'price_per_table' => 'required_if:type,VIP|nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            're_availability_type' => 'required|in:AUTO,MANUAL',
            'auto_re_availability_minutes' => 'nullable|integer|min:1',
            'conditions' => 'sometimes|array',
            'amenities' => 'sometimes|array',
            'media' => 'sometimes|array',
        ]);
        $table = $this->restaurantService->createTable($restaurant, $data);
        return response()->json(['success' => true, 'data' => $table], 201);
    }

    public function update(Request $request, Restaurant $restaurant, RestaurantTable $table)
    {
        $data = $request->validate([
            'name' => 'sometimes|nullable|string|max:120',
            'type' => 'sometimes|in:Normal,VIP',
            'capacity_people' => 'sometimes|integer|min:1',
            'price_per_person' => 'sometimes|nullable|numeric|min:0',
            'price_per_table' => 'sometimes|nullable|numeric|min:0',
            'quantity' => 'sometimes|integer|min:1',
            're_availability_type' => 'sometimes|in:AUTO,MANUAL',
            'auto_re_availability_minutes' => 'sometimes|nullable|integer|min:1',
            'conditions' => 'sometimes|array',
            'amenities' => 'sometimes|array',
            'media' => 'sometimes|array',
        ]);
        $tbl = $this->restaurantService->updateTable($restaurant, $table, $data);
        return response()->json(['success' => true, 'data' => $tbl]);
    }

    public function destroy(Restaurant $restaurant, RestaurantTable $table)
    {
        $this->restaurantService->deleteTable($restaurant, $table);
        return response()->json(['success' => true]);
    }

    private function authorizeRestaurant(Restaurant $restaurant): void
    {
        if ((int)$restaurant->service->user_id !== auth()->id()) { abort(403); }
    }
}

