<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Http\Requests\Search\AdvancedSearchRequest;
use App\Http\Requests\Search\QuickSearchRequest;
use App\Http\Requests\Search\SearchServicesRequest;
use App\Http\Requests\Search\SearchUsersRequest;
use App\Http\Requests\Search\SuggestionsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    public function advancedSearch(AdvancedSearchRequest $request)
    {
        $filters = $request->validated();

        $filters['is_approved'] = true;

        $filtersHash = sha1(json_encode($filters));
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_advanced:v'.$ver.':' . $filtersHash;
        $cached = Cache::remember($cacheKey, 60, function () use ($filters) {
            $payload = $this->searchService->advancedSearch($filters);
            // Wrap services with ServiceResource to ensure relations are loaded consistently
            if (!empty($payload['services'])) {
                $payload['services']->setCollection($payload['services']->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));
            }
            $services = optional($payload['services'])->items() ?? [];
            $maxUpdated = optional(collect($services)->max('updated_at'));
            return [
                'payload' => [ 'success' => true, 'data' => $payload ],
                'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_advanced',$filtersHash,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 120, 60)) {
            return $resp304->header('Vary', 'Authorization, Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            60,
            'public',
            120
        )->header('Vary', 'Authorization, Accept-Encoding');
    }

    public function quickSearch(QuickSearchRequest $request)
    {
        $term = $request->validated()['term'];
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_quick:v'.$ver.':' . sha1($term);
        $cached = Cache::remember($cacheKey, 30, function () use ($term) {
            $results = $this->searchService->quickSearch($term);
            return [
                'payload' => [ 'success' => true, 'data' => $results ],
                'lastModifiedTs' => now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_quick',$term,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 120, 30)) {
            return $resp304->header('Vary', 'Accept-Language, Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            30,
            'public',
            120
        )->header('Vary', 'Accept-Language, Accept-Encoding');
    }

    public function searchServices(SearchServicesRequest $request)
    {
        $filters = $request->validated();

        $filters['is_approved'] = true;

        $filtersHash = sha1(json_encode($filters));
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_services:v'.$ver.':' . $filtersHash . ':p' . (int)($filters['per_page'] ?? 20) . ':pg' . (int)request()->input('page', 1);
        $cached = Cache::remember($cacheKey, 60, function () use ($filters) {
            $services = $this->searchService->searchServices($filters)->paginate($filters['per_page'] ?? 20);
            $services->setCollection($services->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));
            $maxUpdated = optional(collect($services->items())->max('updated_at'));
            return [
                'payload' => [ 'success' => true, 'data' => $services ],
                'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_services',$filtersHash,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 300, 60)) {
            return $resp304->header('Vary', 'Authorization, Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            60,
            'public',
            300
        )->header('Vary', 'Authorization, Accept-Encoding');
    }

    public function searchUsers(SearchUsersRequest $request){
        $filters = $request->validated();
        $userId = auth()->id() ?? 'guest';
        $filtersHash = sha1(json_encode($filters) . $userId);
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_users:v'.$ver.':' . $filtersHash . ':p' . (int)($filters['per_page'] ?? 20) . ':pg' . (int)request()->input('page', 1);
        
        $cached = Cache::remember($cacheKey, 60, function () use ($filters, $userId) {
            
            $usersQuery = $this->searchService->searchUsers($filters);

            $usersQuery->withCount(['followers', 'follows', 'bookings'])
                       ->withAvg('reviews', 'rating');

            if ($userId !== 'guest') {
                $usersQuery->with([
                    'followers' => function($query) use ($userId) {
                        $query->where('follower_id', $userId);
                    },
                    'follows' => function($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }
                ]);
            }
            
            $users = $usersQuery->paginate($filters['per_page'] ?? 20);
            $users->setCollection($users->getCollection()->map(fn($u) => new \App\Http\Resources\UserResource($u)));
            
            $maxUpdated = optional(collect($users->items())->max('updated_at'));
            
            return [
                'payload' => [ 'success' => true, 'data' => $users ],
                'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_users',$filtersHash,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 120, 60)) {
            return $resp304->header('Vary', 'Accept-Language, Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            60,
            'public',
            120
        )->header('Vary', 'Accept-Language, Accept-Encoding');
    }

    public function suggestions(SuggestionsRequest $request)
    {
        $term = $request->validated()['term'];
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_suggestions:v'.$ver.':' . sha1($term);
        $cached = Cache::remember($cacheKey, 30, function () use ($term) {
            $suggestions = $this->searchService->getSearchSuggestions($term);
            return [
                'payload' => [ 'success' => true, 'data' => $suggestions ],
                'lastModifiedTs' => now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_suggestions',$term,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 60, 30)) {
            return $resp304->header('Vary', 'Accept-Language, Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            30,
            'public',
            60
        )->header('Vary', 'Accept-Language, Accept-Encoding');
    }

    public function stats(Request $request)
    {
        $filters = $request->only([
            'category_id', 'service_type', 'min_price', 'max_price',
            'latitude', 'longitude', 'radius'
        ]);

        // Ensure that stats are only calculated for approved services.
        $filters['is_approved'] = true;

        $filtersHash = sha1(json_encode($filters));
        $ver = \App\Support\CacheVersion::get('search');
        $cacheKey = 'search_stats:v'.$ver.':' . $filtersHash;
        $cached = Cache::remember($cacheKey, 30, function () use ($filters) {
            $stats = $this->searchService->getSearchStats($filters);
            return [
                'payload' => [ 'success' => true, 'data' => $stats ],
                'lastModifiedTs' => now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['search_stats',$filtersHash,$cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 60, 30)) {
            return $resp304->header('Vary', 'Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            30,
            'public',
            60
        )->header('Vary', 'Accept-Encoding');
    }

    
    public function nearMe(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius_km' => 'sometimes|numeric|min:1|max:100',
            'gender_type' => 'sometimes|in:male,female,both',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        $radius = (float)($request->radius_km ?? 10);

        $delta = $radius / 111.0; // ~1 degree = 111km
        $minLat = $lat - $delta;
        $maxLat = $lat + $delta;
        $minLng = $lng - $delta;
        $maxLng = $lng + $delta;

        $ver = \App\Support\CacheVersion::get('search');
        $gender = request()->input('gender_type');
        $categoryId = (int)request()->input('category_id');
        $cacheKey = 'search_near_me:v'.$ver.':' . sha1(json_encode([$lat,$lng,$radius,$gender,$categoryId]));
        $cached = Cache::remember($cacheKey, 30, function () use ($minLat,$maxLat,$minLng,$maxLng,$lat,$lng,$radius,$gender,$categoryId) {
            // Prefer services.* coords, else fallback to properties.*
            $services = \App\Models\Service::query()
                ->with(['category','user','property:id,service_id,address,latitude,longitude,place_id'])
                ->where('is_approved', true)
                ->when($gender, function($q) use ($gender){
                    $q->where(function($qq) use ($gender){
                        $qq->where('gender_type', $gender)->orWhereHas('event', fn($e) => $e->where('gender_type', $gender));
                    });
                })
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->where(function($q) use ($minLat,$maxLat,$minLng,$maxLng){
                    $q->where(function($qq) use ($minLat,$maxLat,$minLng,$maxLng){
                        $qq->whereNotNull('latitude')
                           ->whereNotNull('longitude')
                           ->whereBetween('latitude', [$minLat, $maxLat])
                           ->whereBetween('longitude', [$minLng, $maxLng]);
                    })->orWhereHas('property', function($qp) use ($minLat,$maxLat,$minLng,$maxLng){
                        $qp->whereBetween('latitude', [$minLat, $maxLat])
                           ->whereBetween('longitude', [$minLng, $maxLng]);
                    });
                })
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            $results = $services->map(function($s){
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'latitude' => $s->latitude ?? optional($s->property)->latitude,
                    'longitude' => $s->longitude ?? optional($s->property)->longitude,
                    'address' => $s->address ?? optional($s->property)->address,
                    'place_id' => $s->place_id ?? optional($s->property)->place_id,
                    'category_id' => $s->category_id,
                    'user_id' => $s->user_id,
                    'created_at' => $s->created_at,
                ];
            });
            return [
                'payload' => [
                    'success' => true,
                    'data' => $results,
                    'meta' => compact('lat','lng','radius') + ['count' => $results->count()],
                ],
                'lastModifiedTs' => now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['near_me', $lat, $lng, $radius, $cached['payload']['meta']['count'], $cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 60, 30)) {
            return $resp304->header('Vary', 'Accept-Encoding');
        }
        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            optional($lastModified)->copy(),
            30,
            'public',
            60
        )->header('Vary', 'Accept-Encoding');
    }
}

