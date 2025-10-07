<?php

namespace App\Http\Requests\Admin\Properties;

use Illuminate\Foundation\Http\FormRequest;

class PropertyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = auth('api')->user();
        return $u && $u->can('properties.manage');
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|integer|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'place_id' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'region_id' => 'nullable|exists:regions,id',
            'neigbourhood_id' => 'nullable|exists:neigbourhoods,id',

            'property.property_name' => 'required|string|max:255',
            'property.type' => 'required|string|max:50',
            'property.category' => 'nullable|string|max:50',
            'property.area_sqm' => 'nullable|integer|min:0',
            'property.nightly_price' => 'required|numeric|min:0',
            'property.max_adults' => 'nullable|integer|min:1',
            'property.max_children' => 'nullable|integer|min:0',
            'property.child_free_until_age' => 'nullable|integer|min:0',

            'property.bedrooms' => 'nullable|array',
            'property.kitchens' => 'nullable|array',
            'property.pools' => 'nullable|array',
            'property.bathrooms' => 'nullable|array',
            'property.livingRooms' => 'nullable|array',
            'property.facilities' => 'nullable|array',

            'images.*' => 'nullable|image',
        ];
    }
}

