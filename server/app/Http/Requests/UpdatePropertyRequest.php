<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'provider';
    }

    public function rules(): array
    {
        return [
            // Enforced presence for gating simplicity
            'category_id' => 'required|exists:categories,id',

            'name' => 'sometimes|required|string|max:255',
            'property_name' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'address' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'place_id' => 'sometimes|nullable|string|max:255',
            'area_sqm' => 'sometimes|required|integer|min:1',
            'down_payment_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'is_refundable_insurance' => 'sometimes|required|boolean',
            'cancellation_policy' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'allowed_category' => 'sometimes|required|string',
            'checkin_time' => 'sometimes|required|string',
            'checkout_time' => 'sometimes|required|string',
            'access_instructions' => 'sometimes|nullable|string',
            'nightly_price' => 'sometimes|numeric|min:0.01',
            'max_adults' => 'sometimes|nullable|integer|min:1',
            'max_children' => 'sometimes|nullable|integer|min:0',
            'city_id' => 'sometimes|integer|exists:cities,id',
            'region_id' => 'sometimes|integer|exists:regions,id',
            'neigbourhood_id' => 'sometimes|integer|exists:neigbourhoods,id',

            'child_free_until_age' => 'sometimes|nullable|integer|min:0',

            // Structured nested inputs (replace-on-write semantics)
            'bedrooms' => 'sometimes|array',
            'bedrooms.*.beds_count' => 'required|integer|min:1',
            'bedrooms.*.is_master' => 'boolean',

            'living_rooms' => 'sometimes|array',
            'living_rooms.*.type' => 'required|string',

            'pools' => 'sometimes|array',
            'pools.*.length_m' => 'nullable|numeric|min:0',
            'pools.*.width_m' => 'nullable|numeric|min:0',
            'pools.*.depth_m' => 'nullable|numeric|min:0',
            'pools.*.type' => 'nullable|string',
            'pools.*.has_heating' => 'boolean',
            'pools.*.has_barrier' => 'boolean',
            'pools.*.has_water_games' => 'boolean',

            'kitchens' => 'sometimes|array',
            'kitchens.*.dining_chairs' => 'nullable|integer|min:0',
            'kitchens.*.appliances' => 'nullable|array',

            'bathrooms' => 'sometimes|array',
            'bathrooms.*.amenities' => 'nullable|array',

            'facilities' => 'sometimes|array',
            'facilities.*' => 'integer|exists:facilities,id',

            // Media arrays (optional, replace-on-write)
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'sometimes|array',
            'videos.*' => 'file|mimes:mp4,mov,ogg|max:20480',

            'bedroom_photos' => 'sometimes|array',
            'bedroom_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'kitchen_photos' => 'sometimes|array',
            'kitchen_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pool_photos' => 'sometimes|array',
            'pool_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bathroom_photos' => 'sometimes|array',
            'bathroom_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'living_room_photos' => 'sometimes|array',
            'living_room_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}

