<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MainService;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $payload = Cache::remember('home_payload', 60, function () {
            $mainServices = MainService::with(['categories' => function ($q) {
                $q->where('status', true)->limit(8);
            }])->get(['id', 'name']);

            $limit = (int) (get_setting('home_featured_services_limit') ?? 10);

            $featuredServices = Service::query()
                ->with(['category', 'user'])
                ->where('is_approved', true)
                ->orderByDesc('rating_avg')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['id','name','category_id','user_id','price_amount','price_currency','latitude','longitude','address','rating_avg','created_at']);

            return [
                'main_services' => $mainServices,
                'featured_services' => \App\Http\Resources\ServiceResource::collection($featuredServices),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}

