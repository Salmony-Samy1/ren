<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCateringServiceRequest extends FormRequest
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
            'place_id' => ['nullable', 'string'],
            'gender_type' => ['required', 'in:male,female,both'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'country_id' => ['required', 'exists:countries,id'],
            
            // Legal compliance for catering - one refund policy page only
            'terms_accepted' => ['required', 'boolean', 'accepted'], // Accepts catering refund policy only
            'legal_page_ids' => ['nullable', 'array'],
            'legal_page_ids.*' => ['exists:legal_pages,id'],
            
            // Media uploads
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            
            // Catering specific validation
            'catering' => 'required|array',
            'catering.catering_name' => 'required|string|max:255',
            'catering.cuisine_type' => 'required|string|max:255',
            'catering.description' => 'required|string',
            'catering.min_order_amount' => 'required|numeric|min:0',
            'catering.max_order_amount' => 'nullable|numeric|min:0',
            'catering.available_stock' => 'nullable|integer|min:0',
            'catering.preparation_time' => 'nullable|integer|min:0',
            'catering.cancellation_policy' => 'required|string',
            'catering.fulfillment_methods' => 'nullable|array',
            'catering.images' => 'nullable|array',
            'catering.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'catering.videos' => 'nullable|array',
            'catering.videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            
            // Catering items (add-ons)
            'catering_item' => 'nullable|array',
            'catering_item.packages' => 'nullable|array',
            'catering_item.packages.*.package_name' => 'required_with:catering_item.packages|string|max:255',
            'catering_item.packages.*.description' => 'required_with:catering_item.packages|string',
            'catering_item.packages.*.price' => 'required_with:catering_item.packages|numeric|min:0',
            'catering_item.packages.*.available_stock' => 'nullable|integer|min:0',
            'catering_item.packages.*.category_id' => 'required_with:catering_item.packages|exists:catering_item_categories,id',
            'catering_item.packages.*.images' => 'nullable|array',
            'catering_item.packages.*.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
