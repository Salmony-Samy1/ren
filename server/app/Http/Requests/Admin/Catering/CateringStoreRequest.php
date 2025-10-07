<?php

namespace App\Http\Requests\Admin\Catering;

use Illuminate\Foundation\Http\FormRequest;

class CateringStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            // Admin provider selection
            'provider_id' => ['nullable','integer','exists:users,id'],
            
            // EXACTLY same validation as StoreServiceRequest for catering
            'category_id' => ['required','integer','exists:categories,id'],
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'address' => ['nullable','string','max:255'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'place_id' => ['nullable','string','max:255'],
            'gender_type' => ['nullable','string','in:male,female,both'],
            'price_amount' => ['nullable','numeric','min:0'],
            'country_id' => ['nullable','integer','exists:countries,id'],

            // Media uploads
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            
            // CateringItem specific validation (EXACTLY same as StoreCateringServiceRequest)
            'catering' => 'nullable|array',
            'catering.catering_name' => 'nullable|string|max:255',
            'catering.cuisine_type' => 'nullable|string|max:255',
            'catering.description' => 'nullable|string',
            'catering.min_order_amount' => 'nullable|numeric|min:0',
            'catering.max_order_amount' => 'nullable|numeric|min:0',
            'catering.available_stock' => 'nullable|integer|min:0',
            'catering.preparation_time' => 'nullable|integer|min:0',
            'catering.cancellation_policy' => 'nullable|string',
            'catering.images' => 'nullable|array',
            'catering.videos' => 'nullable|array',
            'catering.images.*' => 'string|url',
            'catering.videos.*' => 'string|url',
            
            // Catering-specific fields (EXACTLY same as StoreServiceRequest)
            'catering_name' => 'nullable|string|max:255',
            'cuisine_type' => 'nullable|string|max:255',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0',
            'preparation_time' => 'nullable|integer|min:0',
            'delivery_available' => 'nullable|boolean',
            'delivery_radius_km' => 'nullable|integer|min:0',
            
            // Direct catering items structure (EXACTLY same as StoreServiceRequest)
            'catering_items' => 'nullable|array',
            'catering_items.*.meal_name' => 'nullable|string|max:255',
            'catering_items.*.price' => 'nullable|numeric|min:0',
            'catering_items.*.servings_count' => 'nullable|integer|min:1',
            'catering_items.*.description' => 'nullable|string',
            'catering_items.*.photos' => 'nullable|array',
            'catering_items.*.photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'catering_items.*.availability_stock' => 'nullable|integer|min:0',
            
            // Legacy catering_item structure (EXACTLY same as StoreServiceRequest)
            'catering_item' => 'nullable|array',
            'catering_item.packages' => 'nullable|array',
            'catering_item.packages.*.package_name' => 'required_with:catering_item.packages|string|max:255',
            'catering_item.packages.*.price' => 'required_with:catering_item.packages|numeric|min:0',
            'catering_item.packages.*.available_stock' => 'nullable|integer|min:0',
            'catering_item.available_stock' => 'nullable|array',
            'catering_item.available_stock.*' => 'nullable|integer|min:0',
            'catering_item.packages.*.items' => 'nullable|array',
            'catering_item.packages.*.items.*' => 'string|max:255',
            'catering_item.availability_schedule' => 'nullable|array',
            'catering_item.availability_schedule.*' => 'string|max:32',
        ];
    }
}

