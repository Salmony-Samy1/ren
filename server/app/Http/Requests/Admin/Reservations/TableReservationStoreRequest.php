<?php

namespace App\Http\Requests\Admin\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class TableReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'restaurant_table_id' => ['required','integer','exists:restaurant_tables,id'],
            'user_id' => ['nullable','integer','exists:users,id'],
            'start_time' => ['required','date','before:end_time'],
            'end_time' => ['required','date','after:start_time'],
            'status' => ['nullable','in:confirmed,tentative,cancelled'],
            'notes' => ['nullable','string'],
        ];
    }
}

