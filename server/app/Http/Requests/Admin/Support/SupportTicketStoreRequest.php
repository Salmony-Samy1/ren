<?php

namespace App\Http\Requests\Admin\Support;

use Illuminate\Foundation\Http\FormRequest;

class SupportTicketStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && (auth('api')->user()?->type === 'admin' || auth('api')->user()?->can('support.manage'));
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable','integer','exists:users,id'],
            'subject' => ['required','string','max:255'],
            'message' => ['required','string'],
            'priority' => ['sometimes','in:low,normal,high,urgent'],
            'category' => ['sometimes','string','max:100'],
            'meta' => ['sometimes','array']
        ];
    }
}

