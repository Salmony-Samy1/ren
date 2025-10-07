<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'provider';
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            
            // Basic service fields
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'place_id' => ['required', 'string'],
            'gender_type' => ['required', 'in:male,female,both'],
            'price_currency_id' => ['required', 'exists:currencies,id'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            
            // Media uploads
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            
            // Property specific validation
            'property' => 'required|array',
            'property.property_name' => 'required|string|max:255',
            'property.type' => 'required|string|max:255',
            'property.category' => 'required|string|max:255',
            'property.unit_code' => 'required|string|unique:properties,unit_code',
            'property.area_sqm' => 'required|integer|min:1',
            'property.down_payment_percentage' => 'required|numeric|min:0|max:100',
            'property.is_refundable_insurance' => 'required|boolean',
            'property.cancellation_policy' => 'required|string',
            'property.description' => 'required|string',
            'property.allowed_category' => 'required|string',
            'property.access_instructions' => 'required|string',
            'property.checkin_time' => 'required|string',
            'property.checkout_time' => 'required|string',
            'property.city_id' => 'nullable|integer|exists:cities,id',
            'property.region_id' => 'nullable|integer|exists:regions,id',
            'property.neigbourhood_id' => 'nullable|integer|exists:neigbourhoods,id',
            'property.nightly_price' => 'nullable|numeric|min:0',
            'property.weekly_price' => 'nullable|numeric|min:0',
            'property.monthly_price' => 'nullable|numeric|min:0',
            'property.children_allowed' => 'nullable|boolean',
            'property.max_children' => 'nullable|integer|min:0',
            'property.children_age_min' => 'nullable|integer|min:0',
            'property.children_age_max' => 'nullable|integer|min:0',
            
            // Section-specific photos
            'bedroom_photos' => 'nullable|array',
            'bedroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'kitchen_photos' => 'nullable|array',
            'kitchen_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pool_photos' => 'nullable|array',
            'pool_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bathroom_photos' => 'nullable|array',
            'bathroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'living_room_photos' => 'nullable|array',
            'living_room_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
