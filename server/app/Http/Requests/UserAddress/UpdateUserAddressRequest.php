<?php

namespace App\Http\Requests\UserAddress;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'label' => 'sometimes|nullable|string|max:50',
            'name' => 'sometimes|nullable|string|max:100',
            'phone' => 'sometimes|nullable|string|max:30',
            'country_id' => 'sometimes|nullable|exists:countries,id',
            'address' => 'sometimes|required|string|max:500',
            'street' => 'sometimes|nullable|string|max:255',
            'neighborhood' => 'sometimes|nullable|string|max:255',
            'region' => 'sometimes|nullable|string|max:255',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'place_id' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string|max:1000',
            'type' => 'sometimes|nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
        ];
    }
}

