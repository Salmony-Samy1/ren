<?php

namespace App\Http\Requests\Admin\Support;

use Illuminate\Foundation\Http\FormRequest;

class SupportTicketUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && (auth('api')->user()?->type === 'admin' || auth('api')->user()?->can('support.manage'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required','in:open,pending,in_progress,resolved,closed'],
        ];
    }
}

