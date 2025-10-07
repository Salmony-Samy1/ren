<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of events for the authenticated provider.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $events = Event::with('service')
            ->whereHas('service', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(10); 

        return response()->json(['data' => $events], 200);
    }
    
    /**
     * Display the specified event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Event $event)
    {
        if ($event->service->user_id !== auth()->id() && auth()->user()->type !== 'admin') {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }
        
        return response()->json(['data' => $event->load('service')], 200);
    }

    /**
     * Store a newly created event in storage.
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
     * Update the specified event in storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::update instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Event $event)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $event->service_id
        ], 410); // 410 Gone
    }

    /**
     * Remove the specified event from storage.
     * 
     * @deprecated This method is deprecated. Use MyServicesController::destroy instead.
     * The unified service management is now handled by UnifiedServiceManager.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Event $event)
    {
        return response()->json([
            'message' => 'This endpoint is deprecated. Please use /api/v1/services/{service} endpoint instead.',
            'redirect_to' => '/api/v1/services/' . $event->service_id
        ], 410); // 410 Gone
    }
}