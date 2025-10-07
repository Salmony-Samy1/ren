<?php

if (!function_exists('format_response')) {
    function format_response($success, $message, $data = null, $code = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}


if (!function_exists('get_setting')) {
    function get_setting($key, $default = null)
    {
        $setting = \App\Models\Settings::where('key', $key)->first();
        return $setting?->value ?? $default;
    }
}

if (!function_exists('set_setting')) {
    function set_setting($key, $value)
    {
        $setting = \App\Models\Settings::where('key', $key)->first();
        if ($setting) {
            $setting->value = $value;
            $setting->save();
        } else {
            \App\Models\Settings::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}
