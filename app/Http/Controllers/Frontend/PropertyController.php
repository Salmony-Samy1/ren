<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties for the authenticated provider.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $properties = Property::with('service')
            ->whereHas('service', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10);

        return response()->json(['data' => $properties], 200);
    }

    /**
     * Display the specified property.
     *
     * @param  \App\Models\Property  $property
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Property $property)
    {
        if ($property->service->user_id !== auth()->id() && auth()->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json(['data' => $property->load('service')], 200);
    }

    /**
     * Store a newly created property in storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::store instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services endpoint instead.',
            'redirect_to' => '/api/v1/services'
        ], 410); // 410 Gone
    }

    /**
     * Update the specified property in storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::update instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Property  $property
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Property $property)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $property->service_id
        ], 410); // 410 Gone
    }

    /**
     * Remove the specified property from storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::destroy instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \App\Models\Property  $property
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Property $property)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $property->service_id
        ], 410); // 410 Gone
    }

    /**
     * List media items for a property
     */
    public function media(Property $property)
    {
        $this->authorizeProvider($property);
        return response()->json([
            'images' => $property->getMedia('property_images')->map->only(['id','file_name','mime_type','size','original_url']),
            'videos' => $property->getMedia('property_videos')->map->only(['id','file_name','mime_type','size','original_url']),
        ]);
    }

    /**
     * Upload images to property
     */
    public function addImages(Request $request, Property $property)
    {
        $this->authorizeProvider($property);
        $request->validate(['images' => 'required|array', 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048']);
        foreach ($request->file('images') as $image) {
            $property->addMedia($image)->toMediaCollection('property_images');
        }
        return response()->json(['message' => 'Images uploaded']);
    }

    /**
     * Upload videos to property
     */
    public function addVideos(Request $request, Property $property)
    {
        $this->authorizeProvider($property);
        $request->validate(['videos' => 'required|array', 'videos.*' => 'file|mimes:mp4,mov,ogg|max:20480']);
        foreach ($request->file('videos') as $video) {
            $property->addMedia($video)->toMediaCollection('property_videos');
        }
        return response()->json(['message' => 'Videos uploaded']);
    }

    /**
     * Delete a media item from property
     */
    public function deleteMedia(Property $property, int $mediaId)
    {
        $this->authorizeProvider($property);
        $media = $property->media()->where('id', $mediaId)->firstOrFail();
        $media->delete();
        return response()->json(['message' => 'Media deleted']);
    }

    private function authorizeProvider(Property $property): void
    {
        if ($property->service->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }
    }
}