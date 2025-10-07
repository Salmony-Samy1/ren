<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CountriesController extends Controller
{
    /**
     * Get countries for registration dropdown
     */
    public function index(): JsonResponse
    {
        $countries = Country::active()->ordered()->get([
            'id', 
            'name_ar', 
            'name_en', 
            'code', 
            'flag_emoji',
            'currency_code',
            'currency_symbol'
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Countries fetched successfully'),
            'data' => $countries
        ]);
    }

    /**
     * Get country details by ID
     */
    public function show(Country $country): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('Country details fetched successfully'),
            'data' => [
                'id' => $country->id,
                'name_ar' => $country->name_ar,
                'name_en' => $country->name_en,
                'code' => $country->code,
                'flag_emoji' => $country->flag_emoji,
                'currency_code' => $country->currency_code,
                'currency_name_ar' => $country->currency_name_ar,
                'currency_name_en' => $country->currency_name_en,
                'currency_symbol' => $country->currency_symbol,
                'exchange_rate' => $country->exchange_rate,
                'timezone' => $country->timezone
            ]
        ]);
    }
}
