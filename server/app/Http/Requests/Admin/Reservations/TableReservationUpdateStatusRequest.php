<?php

namespace App\Http\Requests\Admin\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class TableReservationUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required','in:confirmed,tentative,cancelled'],
        ];
    }
}

