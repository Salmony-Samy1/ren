<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Repositories\CategoryRepo\ICategoryRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoriesController extends Controller
{
    public function __construct(private readonly ICategoryRepo $categoryRepo)
    {
    }

    public function index(Request $request)
    {
        $filters = ['status' => true];

        if (filled($request->search)) {
            // Translation-aware search on name to avoid SQL error on categories.name
            $categories = \App\Models\Category::query()
                ->where('status', true)
                ->whereHas('translations', function($t) use ($request) {
                    $t->where('name', 'like', '%' . $request->search . '%');
                })
                ->paginate();
        } else {
            $categories = $this->categoryRepo->getAll(paginated: true, filter: $filters);
        }
        if ($categories) {
            $filtersHash = sha1(json_encode([
                'search' => $request->search,
                'page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
            ]));
            $ver = \App\Support\CacheVersion::get('categories');
            $cacheKey = 'categories_index:v'.$ver.':' . $filtersHash;
            $cached = Cache::remember($cacheKey, 300, function () use ($categories, $request) {
                $maxUpdated = optional(collect($categories->items())->max('updated_at'));
                return [
                    'payload' => (new CategoryCollection($categories))->toArray($request),
                    'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
                ];
            });

            $etag = \App\Support\HttpCache::makeEtag(['categories_index',$filtersHash,$cached['lastModifiedTs']]);
            $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
            if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, $lastModified->copy(), 'public', 600, 120)) {
                return $resp304->header('Vary', 'Accept-Encoding');
            }

            return \App\Support\HttpCache::withValidators(
                response()->json($cached['payload']),
                $etag,
                $lastModified->copy(),
                120,
                'public',
                600
            )->header('Vary', 'Accept-Encoding');
        }
        return format_response(false, __('something went wrong'), code: 500);
    }
}
