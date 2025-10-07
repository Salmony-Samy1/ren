<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchServicesRequest extends FormRequest
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
            'main_service_id' => 'nullable|exists:main_services,id',
            'service_id' => 'nullable|exists:services,id',
            'service_type' => 'nullable|in:event,catering,restaurant,property',
            'service_types' => 'nullable|array',
            'service_types.*' => 'in:event,catering,restaurant,property',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'number_of_people' => 'nullable|integer|min:1',
            'number_of_nights' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|in:created_at,price,rating,distance',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include' => 'nullable|array',
            'include.*' => 'in:booked_by_users,reviews,favorites,category,user,event,restaurant,property',
        ];
    }
}

