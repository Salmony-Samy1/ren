<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class SetCurrencyRequest extends FormRequest
{
    public function authorize(): bool { return auth('api')->check(); }
    public function rules(): array
    {
        return [
            'currency' => 'required|string|in:SAR,BHD',
        ];
    }
}

