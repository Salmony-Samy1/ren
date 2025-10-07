<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CateringItem;
use App\Models\Service;
use App\Models\Category;
use App\Models\MainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CateringController extends Controller
{
    /**
     * Display a listing of catering items for the authenticated provider.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cateringItems = CateringItem::with('service')
            ->whereHas('service', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10);

        return response()->json(['data' => $cateringItems], 200);
    }

    /**
     * Display the specified catering item.
     *
     * @param  \App\Models\CateringItem  $cateringItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(CateringItem $cateringItem)
    {
        if ($cateringItem->service->user_id !== auth()->id() && auth()->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json(['data' => $cateringItem->load('service')], 200);
    }

    /**
     * Store a newly created catering item in storage.
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
     * Update the specified catering item in storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::update instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CateringItem  $cateringItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CateringItem $cateringItem)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $cateringItem->service_id
        ], 410); // 410 Gone
    }

    /**
     * Remove the specified catering item from storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::destroy instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \App\Models\CateringItem  $cateringItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CateringItem $cateringItem)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $cateringItem->service_id
        ], 410); // 410 Gone
    }
}