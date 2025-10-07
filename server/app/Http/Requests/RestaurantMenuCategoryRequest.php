<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantMenuCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * We only allow admins to manage categories.
     */
    public function authorize(): bool
    {
        return $this->user()->type === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'sometimes|boolean',
            'display_order' => 'sometimes|integer',
        ];
    }
}