<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'user_type' => 'nullable|in:customer,provider',
            'is_approved' => 'nullable|boolean',
            'region_id' => 'nullable|exists:regions,id',
            'city_id' => 'nullable|exists:cities,id',
            'min_services' => 'nullable|integer|min:0',
            'min_rating' => 'nullable|numeric|min:1|max:5',
            'sort_by' => 'nullable|in:created_at,services_count,rating',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}

