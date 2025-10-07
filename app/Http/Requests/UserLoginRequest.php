<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'password' => 'required',
            'type' => 'required|string|in:customer,provider',
            'remember' => 'sometimes|boolean'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
