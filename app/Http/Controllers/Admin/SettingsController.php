<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateAssetsRequest;
use App\Http\Requests\Admin\Settings\UpdateEngagementSettingsRequest;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function updateAssets(UpdateAssetsRequest $request)
    {
        $data = $request->validated();
        foreach ($data as $key => $value) {
            if (!is_null($value)) {
                set_setting($key, $value);
            }
        }
        Cache::forget('public_assets');
        return format_response(true, __('Assets updated'));
    }

    public function updateEngagement(UpdateEngagementSettingsRequest $request)
    {
        $data = $request->validated();
        foreach ($data as $key => $value) {
            set_setting($key, $value);
        }
        return format_response(true, __('Engagement settings updated'));
    }

}

