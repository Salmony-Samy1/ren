<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceManagement\UnifiedServiceManager;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MyServicesController extends Controller
{
    public function __construct(
        private readonly UnifiedServiceManager $serviceManager
    ) {}

    public function index()
    {
        $services = $this->serviceManager->getUserServices(Auth::user());
        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            // Debug the incoming data
            Log::info('MyServicesController::store received data', [
                'all_request_keys' => array_keys($request->all()),
                'validated_data_keys' => array_keys($validatedData),
                'has_catering_items' => isset($request->all()['catering_items']),
                'validated_catering_items' => isset($validatedData['catering_items']),
                'catering_items_value' => $validatedData['catering_items'] ?? 'NOT_PRESENT'
            ]);
            
            // استخدام UnifiedServiceManager لإنشاء الخدمة
            $service = $this->serviceManager->createService($validatedData, Auth::user());
            
            Log::info("Service created successfully using UnifiedServiceManager", [
                'service_id' => $service->id,
                'user_id' => auth()->id()
            ]);
            
            return new ServiceResource($this->serviceManager->getService($service));
            
        } catch (\InvalidArgumentException $e) {
            Log::warning("Service creation failed - validation error", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error("Service creation failed - unexpected error", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while creating the service. Please try again.'
            ], 500);
        }
    }


    public function show(Service $service)
    {
        $this->authorize('view', $service);
        return new ServiceResource($this->serviceManager->getService($service));
    }
    

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $this->authorize('update', $service);
        
        try {
            $validatedData = $request->validated();
            
            Log::info('Updating service using UnifiedServiceManager', [
                'service_id' => $service->id,
                'service_type' => $this->getServiceType($service),
                'user_id' => auth()->id()
            ]);
            
            // استخدام UnifiedServiceManager لتحديث الخدمة
            $updatedService = $this->serviceManager->updateService($service, $validatedData);
            
            return new ServiceResource($this->serviceManager->getService($updatedService));
            
        } catch (\InvalidArgumentException $e) {
            Log::warning("Service update failed - validation error", [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error("Service update failed - unexpected error", [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while updating the service. Please try again.'
            ], 500);
        }
    }
    
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
        
        try {
            Log::info('Deleting service using UnifiedServiceManager', [
                'service_id' => $service->id,
                'service_type' => $this->getServiceType($service),
                'user_id' => auth()->id()
            ]);
            
            // استخدام UnifiedServiceManager لحذف الخدمة
            $this->serviceManager->deleteService($service);
            
            return response()->json(null, Response::HTTP_NO_CONTENT);
            
        } catch (\Exception $e) {
            Log::error("Service deletion failed", [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while deleting the service. Please try again.'
            ], 500);
        }
    }

    /**
     * Get service type from service model
     *
     * @param Service $service
     * @return string
     */
    private function getServiceType(Service $service): string
    {
        if ($service->property) return 'property';
        if ($service->event) return 'event';
        if ($service->restaurant) return 'restaurant';
        if ($service->catering) return 'catering';
        
        return 'unknown';
    }
}

