<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class PublicAssetsController extends Controller
{
    public function index()
    {
        $data = Cache::remember('public_assets', 300, function () {
            $banners = Banner::query()
                ->where('active', true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->orderBy('placement')
                ->orderBy('sort_order')
                ->get(['id', 'title', 'image_url', 'link_url', 'placement', 'sort_order']);

            $assets = [
                'app_logo_url' => get_setting('app_logo_url', ''),
                'gift_background_url' => get_setting('gift_background_url', ''),
            ];

            return [
                'assets' => $assets,
                'banners' => $banners->groupBy('placement')->map->values(),
            ];
        });

        return format_response(true, __('api.assets.title'), $data);
    }
}

