<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredOrSometimes = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name'        => [$requiredOrSometimes, 'string', 'max:120'],
            'description' => 'nullable|string',
            'price'       => [$requiredOrSometimes, 'numeric', 'min:0'],
            'is_active'   => 'sometimes|boolean',
            
            'restaurant_menu_category_id' => [
                $requiredOrSometimes,
                'integer',
                'exists:restaurant_menu_categories,id'
            ],
            
            'image' => [$requiredOrSometimes, 'image', 'mimes:jpeg,png,jpg,gif', 'max:4096'],
        ];
    }
}