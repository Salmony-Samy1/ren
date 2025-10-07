<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\LogUserActivity;
use App\Models\Service;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use LogUserActivity;
    /**
     * Display a listing of the authenticated user's favorite services.
     * This now works for both 'customer' and 'provider' user types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
public function index(Request $request)
{
    $favoriteServiceIds = auth()->user()->favorites()->pluck('service_id')->all();
    if (empty($favoriteServiceIds)) {
        return response()->json(['data' => []], 200);
    }

    $include = $request->input('include', []);
    if (!is_array($include)) { $include = [$include]; }

    // Always include 'favorites' so ServiceResource exposes is_favorited and favorites_count when requested
    if (!in_array('favorites', $include)) { $include[] = 'favorites'; }

    $search = app(\App\Services\SearchService::class);
    $filters = [
        'service_ids' => $favoriteServiceIds,
        'include' => $include,
    ];
    $services = $search->searchServices($filters)->get();

    return response()->json([
        'data' => \App\Http\Resources\ServiceResource::collection($services)
    ], 200);
}

    /**
     * Add a service to the authenticated user's favorites.
     * This now works for both 'customer' and 'provider' user types.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id'
        ]);

        $service = Service::find($request->service_id);

        if (!$service) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        $favorite = Favorite::where('user_id', auth()->id())
                            ->where('service_id', $service->id)
                            ->first();

        if ($favorite) {
            // Return existing favorited service shape instead of 409 to keep UX simple
            $service->load(['category','user','event','property','restaurant']);
            return response()->json([
                'message' => 'Service is already in favorites.',
                'data' => new \App\Http\Resources\ServiceResource($service)
            ], 200);
        }

        auth()->user()->favorites()->create(['service_id' => $service->id]);

        // تسجيل نشاط إضافة للمفضلة
        $this->logFavorite($service->id, 'add_favorite');

        // Reload and return consistent shape
        $service->load(['category','user','event','property','restaurant']);
        // Mark as favorited in the response
        $service->setAttribute('is_favorited', true);
        return response()->json([
            'message' => 'Service added to favorites successfully.',
            'data' => new \App\Http\Resources\ServiceResource($service)
        ], 201);
    }

    /**
     * Remove a service from the authenticated user's favorites.
     * This now works for both 'customer' and 'provider' user types.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Service $service)
    {
        $favorite = auth()->user()->favorites()->where('service_id', $service->id)->first();

        if (!$favorite) {
            return response()->json(['message' => 'Service is not in favorites.'], 404);
        }

        $favorite->delete();

        // تسجيل نشاط إزالة من المفضلة
        $this->logFavorite($service->id, 'remove_favorite');

        return response()->json(['message' => 'Service removed from favorites successfully.'], 200);
    }
}
