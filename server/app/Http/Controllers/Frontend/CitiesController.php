<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityCollection;
use App\Repositories\CityRepo\ICityRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Support\HttpCache;
use App\Http\Resources\RegionCollection;

class CitiesController extends Controller
{

    public function __construct(private readonly ICityRepo $cityRepo)
    {
    }

    public function index()
    {
        $regions = $this->cityRepo->getAll();
        $maxUpdated = optional(collect($regions)->max('updated_at'));
        $etag = \App\Support\HttpCache::makeEtag(['regions_index', $maxUpdated?->timestamp, $regions->count()]);
        $lastModified = $maxUpdated ?: now();
        
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 600, 120)) {
            return $resp304->header('Vary', 'Accept-Encoding');
        }

        // 1. جهّز بيانات الـ JSON
        $data = (new RegionCollection($regions))->toArray(request());

        // 2. أنشئ استجابة أساسية (Response) وضع فيها البيانات مع الـ header الصحيح
        $response = response()->make(json_encode($data))
                            ->header('Content-Type', 'application/json');

        // 3. مرّر كائن الـ Response الجديد للدالة
        return \App\Support\HttpCache::withValidators(
            $response,
            $etag,
            optional($lastModified)->copy(),
            120,
            'public',
            600
        )->header('Vary', 'Accept-Encoding');
    }
}
