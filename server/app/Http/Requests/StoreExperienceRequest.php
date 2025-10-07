<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'main_service_id' => 'required|integer|exists:main_services,id',
            'caption'         => 'nullable|string|max:2000',
            'images'          => 'required|array|min:1',
            'images.*'        => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }
}