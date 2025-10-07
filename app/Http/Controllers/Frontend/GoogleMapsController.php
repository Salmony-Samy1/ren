<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GoogleMapsController extends Controller
{
    public function __construct(private readonly GoogleMapsService $mapsService)
    {
    }

    /**
     * الحصول على إحداثيات من العنوان
     */
    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->geocode($request->address);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * الحصول على العنوان من الإحداثيات
     */
    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->reverseGeocode($request->latitude, $request->longitude);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * حساب المسافة بين نقطتين
     */
    public function calculateDistance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'dest_lat' => 'required|numeric|between:-90,90',
            'dest_lng' => 'required|numeric|between:-180,180',
            'unit' => 'nullable|in:km,m',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->calculateDistance(
            $request->origin_lat,
            $request->origin_lng,
            $request->dest_lat,
            $request->dest_lng,
            $request->unit ?? 'km'
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * البحث عن الأماكن القريبة
     */
    public function nearbyPlaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'type' => 'nullable|string',
            'radius' => 'nullable|integer|min:100|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->findNearbyPlaces(
            $request->latitude,
            $request->longitude,
            $request->type,
            $request->radius ?? 5000
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * البحث عن الأماكن
     */
    public function searchPlaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->searchPlaces(
            $request->query,
            $request->latitude,
            $request->longitude,
            $request->radius ?? 5000
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * الحصول على تفاصيل مكان
     */
    public function placeDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->getPlaceDetails($request->place_id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * الحصول على اتجاهات
     */
    public function directions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'dest_lat' => 'required|numeric|between:-90,90',
            'dest_lng' => 'required|numeric|between:-180,180',
            'mode' => 'nullable|in:driving,walking,bicycling,transit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->getDirections(
            $request->origin_lat,
            $request->origin_lng,
            $request->dest_lat,
            $request->dest_lng,
            $request->mode ?? 'driving'
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * البحث عن الخدمات القريبة
     */
    public function nearbyServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:50000',
            'service_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // البحث عن الخدمات في قاعدة البيانات القريبة
        $services = \App\Models\Service::where('is_approved', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($request->service_type, function ($query, $type) {
                return $query->whereHas($type);
            })
            ->get()
            ->filter(function ($service) use ($request) {
                // حساب المسافة
                $distance = $this->calculateHaversineDistance(
                    $request->latitude,
                    $request->longitude,
                    $service->latitude,
                    $service->longitude
                );
                
                return $distance <= ($request->radius ?? 5000);
            })
            ->sortBy(function ($service) use ($request) {
                return $this->calculateHaversineDistance(
                    $request->latitude,
                    $request->longitude,
                    $service->latitude,
                    $service->longitude
                );
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services,
                'total' => $services->count(),
                'radius' => $request->radius ?? 5000,
            ]
        ]);
    }

    /**
     * حساب المسافة باستخدام صيغة هافرساين
     */
    private function calculateHaversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // نصف قطر الأرض بالمتر

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
