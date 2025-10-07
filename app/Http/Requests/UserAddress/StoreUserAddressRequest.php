<?php

namespace App\Http\Requests\UserAddress;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'country_id' => 'nullable|exists:countries,id',
            'address' => 'required|string|max:500',
            'street' => 'sometimes|nullable|string|max:255',
            'neighborhood' => 'sometimes|nullable|string|max:255',
            'region' => 'sometimes|nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'place_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
        ];
    }
}

