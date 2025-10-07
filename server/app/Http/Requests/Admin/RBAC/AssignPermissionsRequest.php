<?php

namespace App\Http\Requests\Admin\RBAC;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required','array','min:1'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ];
    }
}

