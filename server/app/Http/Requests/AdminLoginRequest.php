<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'sometimes|boolean'

        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
