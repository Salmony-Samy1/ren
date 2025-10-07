<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CountriesController extends Controller
{
    /**
     * Display a listing of countries
     */
    public function index(Request $request): JsonResponse
    {
        $query = Country::query();

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('currency_code')) {
            $query->where('currency_code', $request->currency_code);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('iso_code', 'like', "%{$search}%");
            });
        }

        $countries = $query->ordered()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => __('Countries fetched successfully'),
            'data' => $countries->items(),
            'meta' => [
                'current_page' => $countries->currentPage(),
                'per_page' => $countries->perPage(),
                'total' => $countries->total(),
                'last_page' => $countries->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created country
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'code' => 'required|string|max:5|unique:countries,code',
            'iso_code' => 'required|string|max:2|unique:countries,iso_code',
            'currency_code' => 'required|string|max:3',
            'currency_name_ar' => 'required|string|max:50',
            'currency_name_en' => 'required|string|max:50',
            'currency_symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'flag_emoji' => 'required|string|max:10',
            'timezone' => 'required|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        $country = Country::create($request->all());

        // Add translations
        $country->translateOrNew('en')->name = $request->name_en;
        $country->translateOrNew('ar')->name = $request->name_ar;
        $country->save();

        return response()->json([
            'success' => true,
            'message' => __('Country created successfully'),
            'data' => $country
        ], 201);
    }

    /**
     * Display the specified country
     */
    public function show(Country $country): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('Country fetched successfully'),
            'data' => $country
        ]);
    }

    /**
     * Update the specified country
     */
    public function update(Request $request, Country $country): JsonResponse
    {
        $request->validate([
            'name_ar' => 'sometimes|string|max:100',
            'name_en' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:5|unique:countries,code,' . $country->id,
            'iso_code' => 'sometimes|string|max:2|unique:countries,iso_code,' . $country->id,
            'currency_code' => 'sometimes|string|max:3',
            'currency_name_ar' => 'sometimes|string|max:50',
            'currency_name_en' => 'sometimes|string|max:50',
            'currency_symbol' => 'sometimes|string|max:10',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'flag_emoji' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0'
        ]);

        $country->update($request->all());

        // Update translations if provided
        if ($request->has('name_en')) {
            $country->translateOrNew('en')->name = $request->name_en;
        }
        if ($request->has('name_ar')) {
            $country->translateOrNew('ar')->name = $request->name_ar;
        }
        $country->save();

        return response()->json([
            'success' => true,
            'message' => __('Country updated successfully'),
            'data' => $country
        ]);
    }

    /**
     * Remove the specified country
     */
    public function destroy(Country $country): JsonResponse
    {
        // Check if country has users
        if ($country->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot delete country with existing users')
            ], 422);
        }

        $country->delete();

        return response()->json([
            'success' => true,
            'message' => __('Country deleted successfully')
        ]);
    }

    /**
     * Get countries for dropdown
     */
    public function dropdown(): JsonResponse
    {
        $countries = Country::active()->ordered()->get(['id', 'name_ar', 'name_en', 'code', 'flag_emoji']);

        return response()->json([
            'success' => true,
            'message' => __('Countries fetched successfully'),
            'data' => $countries
        ]);
    }

    /**
     * Get country statistics
     */
    public function statistics(Country $country): JsonResponse
    {
        $stats = [
            'total_users' => $country->users()->count(),
            'active_users' => $country->users()->where('status', 'active')->count(),
            'total_services' => $country->users()->withCount('services')->sum('services_count'),
            'total_revenue' => $country->users()->withSum('wallet', 'balance')->sum('wallet_sum_balance'),
            'currency_info' => [
                'code' => $country->currency_code,
                'name_ar' => $country->currency_name_ar,
                'name_en' => $country->currency_name_en,
                'symbol' => $country->currency_symbol,
                'exchange_rate' => $country->exchange_rate
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => __('Country statistics fetched successfully'),
            'data' => $stats
        ]);
    }
}
