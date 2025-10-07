<?php

namespace App\Http\Requests;

class ActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'neigbourhood_id' => ['required', 'exists:neigbourhoods,id'],
            'longitude' => ['required', 'numeric'],
            'latitude' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'gender' => ['required', 'in:male,female,both'],
            'price' => ['required', 'numeric'],
            'images' => ['required', 'array'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg|max:2048'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
