<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\PropertySearchRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ServiceResource;
use App\Models\Category;
use App\Services\SearchService;
use Illuminate\Http\Request;

class PropertyBrowseController extends Controller
{
    public function __construct(private readonly SearchService $search)
    {
    }

    // GET /api/v1/public/properties/categories
    public function categories(Request $request)
    {
        $cats = Category::query()
            ->where('status', true)
            ->where('main_service_id', 4)
            ->with('translations')
            ->orderBy('id', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => CategoryResource::collection($cats)]);
    }

    // GET /api/v1/public/properties/search
    public function search(PropertySearchRequest $request)
    {
        $filters = $request->validated();
        // Force scope to properties main service 4
        $filters['service_type'] = 'property';
        $filters['main_service_id'] = 4;

        // Full-text-ish search on service name or property description handled in SearchService via 'search'
        $query = $this->search->searchServices($filters);

        $perPage = (int) ($filters['per_page'] ?? 20);
        if ($perPage < 1) { $perPage = 20; }
        if ($perPage > 100) { $perPage = 100; }

        $paginator = $query->paginate($perPage)->withQueryString();
        return ServiceResource::collection($paginator);
    }
}

