<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['nullable','string','max:64'],
            'points_to_use' => ['nullable','integer','min:0'],
        ];
    }
}

