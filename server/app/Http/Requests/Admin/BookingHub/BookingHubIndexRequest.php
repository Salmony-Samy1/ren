<?php

namespace App\Http\Requests\Admin\BookingHub;

use Illuminate\Foundation\Http\FormRequest;

class BookingHubIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'types' => ['sometimes','array'],
            'types.*' => ['in:service,table,event'],
            'type' => ['sometimes','in:service,table,event'],
            'city_id' => ['sometimes','integer','exists:cities,id'],
            'provider_id' => ['sometimes','integer','exists:users,id'],
            'status' => ['sometimes','string'],
            'date_from' => ['sometimes','date'],
            'date_to' => ['sometimes','date','after_or_equal:date_from'],
            'q' => ['sometimes','string','max:100'],
            'per_page' => ['sometimes','integer','min:1','max:50'],
        ];
    }
}

