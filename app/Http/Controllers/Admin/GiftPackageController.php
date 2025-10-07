<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftPackage;
use Illuminate\Http\Request;

class GiftPackageController extends Controller
{
    public function index()
    {
        $packages = GiftPackage::query()->orderBy('sort_order')->paginate(20);
        return format_response(true, __('admin.gift_packages.title'), $packages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        $package = GiftPackage::create($data);
        return format_response(true, __('Created'), $package);
    }

    public function update(Request $request, GiftPackage $giftPackage)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        $giftPackage->update($data);
        return format_response(true, __('Updated'), $giftPackage);
    }

    public function destroy(GiftPackage $giftPackage)
    {
        $giftPackage->delete();
        return format_response(true, __('Deleted'));
    }
}

