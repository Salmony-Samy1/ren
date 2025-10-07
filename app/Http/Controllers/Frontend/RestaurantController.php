<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    /**
     * Display a listing of restaurants for the authenticated provider.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $restaurants = Restaurant::with('service')
            ->whereHas('service', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10);

        return response()->json(['data' => $restaurants], 200);
    }

    /**
     * Display the specified restaurant.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Restaurant $restaurant)
    {
        if ($restaurant->service->user_id !== auth()->id() && auth()->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }
        
        return response()->json(['data' => $restaurant->load('service')], 200);
    }

    /**
     * Store a newly created restaurant in storage.
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
     * Update the specified restaurant in storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::update instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Restaurant $restaurant)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $restaurant->service_id
        ], 410); // 410 Gone
    }

    /**
     * Remove the specified restaurant from storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::destroy instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Restaurant $restaurant)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $restaurant->service_id
        ], 410); // 410 Gone
    }
}