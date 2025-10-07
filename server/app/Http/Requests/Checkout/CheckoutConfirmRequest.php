<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutConfirmRequest extends FormRequest
{
    public function authorize(): bool { return auth('api')->check(); }

    public function rules(): array
    {
        return [
            'coupon_code' => ['nullable','string','max:64'],
            'points_to_use' => ['nullable','integer','min:0'],
            'idempotency_key' => ['required','string','max:128'],
            'payment_method' => ['required','string','in:wallet,apple_pay,visa,mada,samsung_pay,benefit,stcpay'],
        ];
    }
}

