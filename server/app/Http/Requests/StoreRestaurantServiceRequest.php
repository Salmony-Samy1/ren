<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantServiceRequest extends FormRequest
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
            
            // Restaurant specific validation
            'restaurant' => 'required|array',
            'restaurant.restaurant_name' => 'required|string|max:255',
            'restaurant.cuisine_type' => 'required|string|max:255',
            'restaurant.description' => 'required|string',
            'restaurant.min_order_amount' => 'nullable|numeric|min:0',
            'restaurant.max_capacity' => 'nullable|integer|min:1',
            'restaurant.operating_hours' => 'nullable|array',
            'restaurant.booking_hours' => 'nullable|array',
            'restaurant.cancellation_policy' => 'required|string',
            'restaurant.images' => 'nullable|array',
            'restaurant.videos' => 'nullable|array',
        ];
    }
}
