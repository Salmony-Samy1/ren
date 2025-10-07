<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventServiceRequest extends FormRequest
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
            
            
            // Event specific validation
            'event' => 'required|array',
            'event.event_name' => 'required|string|max:255',
            'event.description' => 'required|string',
            'event.max_individuals' => 'required|integer|min:1',
            'event.meeting_point' => 'required|string',
            'event.age_min' => 'nullable|integer|min:0|max:150',
            'event.age_max' => 'nullable|integer|min:0|max:150|gte:event.age_min',
            'event.gender_type' => 'required|in:male,female,both',
            'event.hospitality_available' => 'required|boolean',
            'event.pricing_type' => 'required|string',
            'event.base_price' => 'required|numeric|min:0',
            'event.discount_price' => 'nullable|numeric|min:0',
            'event.prices_by_age' => 'nullable|array',
            'event.cancellation_policy' => 'required|string',
            'event.language' => 'required|in:ar,en,both',
            'event.start_at' => 'required|date',
            'event.end_at' => 'required|date|after_or_equal:event.start_at',
        ];
    }
}
