<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantTablesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'user_type:admin', 'throttle:admin']);
    }

    /**
     * عرض جميع طاولات مطعم معين
     */
    public function index(Request $request, Restaurant $restaurant)
    {
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
     * إنشاء طاولة جديدة
     */
    public function store(RestaurantTableRequest $request, Restaurant $restaurant)
    {
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
        try {
            // التحقق من أن الطاولة تنتمي للمطعم
            if ((int)$table->restaurant_id !== (int)$restaurant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found in this restaurant'
                ], 404);
            }

            $table->load(['restaurant.service', 'tableReservations.user', 'media']);

            return response()->json([
                'success' => true,
                'data' => new RestaurantTableResource($table),
                'restaurant_info' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->service->name ?? 'Unknown Restaurant',
                    'description' => $restaurant->service->description ?? null,
                ]
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
        try {
            // التحقق من أن الطاولة تنتمي للمطعم
            if ((int)$table->restaurant_id !== (int)$restaurant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found in this restaurant'
                ], 404);
            }

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
     * رفع عدة صور للطاولة
     */
    public function uploadMultipleImages(Request $request, Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                $media = $table->addMedia($image)
                    ->toMediaCollection('table_images');

                $uploadedImages[] = [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumbnail' => $media->getUrl('thumb'),
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف صورة من الطاولة
     */
    public function deleteImage(Restaurant $restaurant, RestaurantTable $table, $mediaId)
    {
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
     * حذف جميع صور الطاولة
     */
    public function deleteAllImages(Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        try {
            $table->clearMediaCollection('table_images');

            return response()->json([
                'success' => true,
                'message' => 'All images deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إعادة ترتيب صور الطاولة
     */
    public function reorderImages(Request $request, Restaurant $restaurant, RestaurantTable $table)
    {
        // التحقق من أن الطاولة تنتمي للمطعم
        if ((int)$table->restaurant_id !== (int)$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found in this restaurant'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array',
            'image_ids.*' => 'required|integer|exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imageIds = $request->input('image_ids');
            
            foreach ($imageIds as $index => $mediaId) {
                $media = $table->getMedia('table_images')->where('id', $mediaId)->first();
                if ($media) {
                    $media->order_column = $index + 1;
                    $media->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Images reordered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder images',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
