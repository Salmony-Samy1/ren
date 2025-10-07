<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCartItemRequest extends FormRequest
{
    public function authorize(): bool { return auth('api')->check(); }

    public function rules(): array
    {
        return [
            'service_id' => ['required','integer','exists:services,id'],
            'quantity' => ['sometimes','integer','min:1'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
        ];
    }
}

