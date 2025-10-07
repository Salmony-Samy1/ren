<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'provider';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'property_name' => 'required|string',
            'type' => 'required|string',
            'category' => 'required|string',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'place_id' => 'nullable|string|max:255',
            'price_currency' => 'required|in:SAR,BHD',
            'area_sqm' => 'required|integer|min:1',
            'down_payment_percentage' => 'required|numeric|min:0|max:100',
            'is_refundable_insurance' => 'required|boolean',
            'cancellation_policy' => 'required|string',
            'description' => 'required|string',
            'allowed_category' => 'required|string',
            'checkin_time' => 'required|string',
            'checkout_time' => 'required|string',
            'nightly_price' => 'required|numeric|min:0.01',
            'max_adults' => 'nullable|integer|min:1',
            'max_children' => 'nullable|integer|min:0',
            'city_id' => 'nullable|integer|exists:cities,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'neigbourhood_id' => 'nullable|integer|exists:neigbourhoods,id',

            'child_free_until_age' => 'nullable|integer|min:0',

            // Structured nested inputs
            'bedrooms' => 'nullable|array',
            'bedrooms.*.beds_count' => 'required|integer|min:1',
            'bedrooms.*.is_master' => 'boolean',

            'living_rooms' => 'nullable|array',
            'living_rooms.*.type' => 'required|string',

            'pools' => 'nullable|array',
            'pools.*.length_m' => 'nullable|numeric|min:0',
            'pools.*.width_m' => 'nullable|numeric|min:0',
            'pools.*.depth_m' => 'nullable|numeric|min:0',
            'pools.*.type' => 'nullable|string',
            'pools.*.has_heating' => 'boolean',
            'pools.*.has_barrier' => 'boolean',
            'pools.*.has_water_games' => 'boolean',

            'kitchens' => 'nullable|array',
            'kitchens.*.dining_chairs' => 'nullable|integer|min:0',
            'kitchens.*.appliances' => 'nullable|array',

            'bathrooms' => 'nullable|array',
            'bathrooms.*.amenities' => 'nullable|array',

            'facilities' => 'nullable|array',
            'facilities.*' => 'integer|exists:facilities,id',

            // Photos per section
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bedroom_photos' => 'nullable|array',
            'bedroom_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'kitchen_photos' => 'nullable|array',
            'kitchen_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pool_photos' => 'nullable|array',
            'pool_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bathroom_photos' => 'nullable|array',
            'bathroom_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'living_room_photos' => 'nullable|array',
            'living_room_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}

