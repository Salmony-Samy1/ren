<?php

namespace App\Http\Requests\Admin\Properties;

use Illuminate\Foundation\Http\FormRequest;

class PropertyPricingRuleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = auth('api')->user();
        return $u && $u->can('properties.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:100',
            'type' => 'required|in:fixed,percent',
            'amount' => 'required|numeric',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'min_stay_nights' => 'nullable|integer|min:1',
            'max_stay_nights' => 'nullable|integer|min:1',
            'meta' => 'nullable|array',
        ];
    }
}

