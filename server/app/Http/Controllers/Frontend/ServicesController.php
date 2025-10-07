<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Repositories\ServiceRepo\IServiceRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ServiceResource;

class ServicesController extends Controller
{
    public function __construct(private readonly IServiceRepo $serviceRepo)
    {
    }

    public function index(Category $category, Request $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = 10;

        $filters = [
            'category_id' => $category->id,
            'is_approved' => true,
        ];
        // === END OF CORRECTION ===

        if (filled($request->search)) {
            $filters['name'] = ['like' => '%' . $request->search . '%'];
        }

        if (filled($latitude) && filled($longitude)) {
            $services = $this->serviceRepo->getByLocation(
                latitude: $latitude,
                longitude: $longitude,
                radius: $radius,
                sort: 'created_at',
                direction: 'desc',
                paginated: true,
                filter: $filters,
                relations: ['category', 'user', 'event','property','restaurant','catering','catering.items']
            );
        } else {
            $services = $this->serviceRepo->getAll(
                sort: 'created_at',
                direction: 'desc',
                paginated: true,
                filter: $filters,
                relations: ['category', 'user', 'event','property','restaurant','catering','catering.items']
            );
        }

        if ($services) {

            $filtersHash = sha1(json_encode([
                'category_id' => $category->id,
                'search' => $request->search,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('per_page', 20),
            ]));
            $ver = \App\Support\CacheVersion::get('services');
            $cacheKey = 'services_index:v' . $ver . ':' . $filtersHash;
            $cached = Cache::remember($cacheKey, 60, function () use ($services) {
                $maxUpdated = optional(collect($services->items())->max('updated_at'));
                $payload = [
                    'success' => true,
                    'message' => __('services.fetched_successfully'),
                    'data' => $services->toArray(),
                ];
                return [
                    'payload' => $payload,
                    'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
                ];
            });

            $etag = \App\Support\HttpCache::makeEtag(['services_index', $filtersHash, $cached['lastModifiedTs']]);
            $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
            if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, $lastModified->copy(), 'public', 300, 60)) {
                return $resp304->header('Vary', 'Authorization, Accept-Encoding');
            }

            return \App\Support\HttpCache::withValidators(
                response()->json($cached['payload']),
                $etag,
                $lastModified->copy(),
                60,
                'public',
                300
            )->header('Vary', 'Authorization, Accept-Encoding');
        }

        return response()->json(['message' => __('something went wrong')], 500);
    }

    public function show(Service $service)
    {
        if (!$service->is_approved || is_null($service->approved_at)) {
            abort(404, 'Service not found or is not currently approved.');
        }

        $service->load([
            'category',
            'user.companyProfile',
            'event',
            'property',
            'restaurant',
            'restaurant.tables',
            'restaurant.menuItems',
            'catering.items',
            'favorites.user',
            'bookings.user',
            'reviews',
        ]);

        $etag = \App\Support\HttpCache::makeEtag(['service_show', $service->id, $service->updated_at?->timestamp]);
        $lastModified = $service->updated_at ?? $service->created_at;
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 300, 120)) {
            return $resp304->header('Vary', 'Authorization, Accept-Encoding');
        }

        $resource = new ServiceResource($service);

        return \App\Support\HttpCache::withValidators(
            response()->json(['success' => true, 'data' => $resource]),
            $etag,
            optional($lastModified)->copy(),
            120,
            'public',
            300
        )->header('Vary', 'Authorization, Accept-Encoding');
    }
}
