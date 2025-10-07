<?php

namespace App\Http\Requests\Admin\RBAC;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'roles' => ['required','array','min:1'],
            'roles.*' => ['integer','exists:roles,id'],
        ];
    }
}

