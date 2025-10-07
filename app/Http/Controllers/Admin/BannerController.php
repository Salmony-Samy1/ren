<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banners\StoreBannerRequest;
use App\Http\Requests\Admin\Banners\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::query()->orderBy('placement')->orderBy('sort_order')->paginate(20);
        return format_response(true, __('admin.banners.title'), $banners);
    }

    public function store(StoreBannerRequest $request)
    {
        $banner = Banner::create($request->validated());
        Cache::forget('public_assets');
        return format_response(true, __('Created'), $banner);
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $banner->update($request->validated());
        Cache::forget('public_assets');
        return format_response(true, __('Updated'), $banner);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        Cache::forget('public_assets');
        return format_response(true, __('Deleted'));
    }
}

