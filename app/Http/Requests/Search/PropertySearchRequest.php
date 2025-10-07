<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class PropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',

            // Scope strictly to properties (main service 4)
            'main_service_id' => 'nullable|in:4',
            'service_type' => 'nullable|in:property',

            // Availability window
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',

            // Location filters
            'city_id' => 'nullable|integer|exists:cities,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'neigbourhood_id' => 'nullable|integer|exists:neigbourhoods,id',

            // Pricing & rating
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'min_rating' => 'nullable|numeric|min:0|max:5',

            // Property-specific
            'min_bedrooms' => 'nullable|integer|min:1',
            'number_of_nights' => 'nullable|integer|min:1',

            // Sorting & pagination
            'sort_by' => 'nullable|in:created_at,price,rating,distance',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',

            // Optional include expansions (reuse existing ones)
            'include' => 'nullable|array',
            'include.*' => 'in:booked_by_users,reviews,favorites,category,user,property',
        ];
    }
}

