<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Models\Service;
use App\Http\Resources\ServiceResource;

class CateringBrowseController extends Controller
{
    public function __construct(private readonly SearchService $search)
    {
    }

    // GET /api/v1/public/catering
    public function index(Request $request)
    {
        $filters = $request->all();
        $filters['service_type'] = 'catering';

        $query = $this->search->searchServices($filters);
        // Pagination controls: per_page [1..50]
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) { $perPage = 15; }
        if ($perPage > 50) { $perPage = 50; }

        $paginator = $query->paginate($perPage)->withQueryString();
        return ServiceResource::collection($paginator);
    }

    // GET /api/v1/public/catering/by-category/{category}
    public function byCategory(Request $request, int $category)
    {
        $filters = $request->all();
        $filters['service_type'] = 'catering';
        $filters['category_id'] = $category;

        $query = $this->search->searchServices($filters);
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) { $perPage = 15; }
        if ($perPage > 50) { $perPage = 50; }

        $paginator = $query->paginate($perPage)->withQueryString();
        return ServiceResource::collection($paginator);
    }

    // GET /api/v1/public/catering/{service}
    public function show(Service $service)
    {
        abort_unless($service->catering()->exists(), 404);
        $service->load([
            'category',
            'user',
            'catering.items',
            'favorites.user',
            'bookings.user',
            'reviews',
        ]);
        return new ServiceResource($service);
    }
}

