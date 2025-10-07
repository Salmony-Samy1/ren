<?php

namespace App\Http\Requests\Admin\Properties;

use Illuminate\Foundation\Http\FormRequest;

class PropertyCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = auth('api')->user();
        return $u && $u->can('properties.view');
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ];
    }
}

