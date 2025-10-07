<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'required|exists:permissions,id'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
