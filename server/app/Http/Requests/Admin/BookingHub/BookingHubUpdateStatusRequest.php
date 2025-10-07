<?php

namespace App\Http\Requests\Admin\BookingHub;

use Illuminate\Foundation\Http\FormRequest;

class BookingHubUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'type' => ['required','in:service,table,event'],
            'status' => ['required','string','max:50'],
        ];
    }
}

