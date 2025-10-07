<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class SetCartAddressRequest extends FormRequest
{
    public function authorize(): bool { return auth('api')->check(); }

    public function rules(): array
    {
        return [
            'address_id' => ['required','integer','exists:user_addresses,id'],
        ];
    }
}

