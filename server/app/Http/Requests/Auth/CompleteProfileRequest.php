<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'gender' => 'required|in:male,female',
            'region_id' => 'required|integer|exists:regions,id',
            'neigbourhood_id' => 'required|integer|exists:neigbourhoods,id',
            'age' => 'nullable|integer|min:13|max:120',
            'city' => 'nullable|string|max:255',
            'national_id' => 'required|string|max:50|unique:users,national_id',
        ];
    }
}

