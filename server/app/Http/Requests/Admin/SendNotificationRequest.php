<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
            'locale' => ['nullable', 'string', 'in:ar,en'],
        ];
    }
}

