<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|in:SAR,BHD',
            'payment_method' => 'required|in:wallet,apple_pay,visa,mada,samsung_pay,benefit,stcpay,tap_card,tap_benefit,tap_apple_pay,tap_google_pay,tap_benefitpay',
            'booking_id' => 'nullable|exists:bookings,id',
            'apple_pay_token' => 'required_if:payment_method,apple_pay,tap_apple_pay|string',
            'samsung_pay_token' => 'required_if:payment_method,samsung_pay|string',
            'phone_number' => 'required_if:payment_method,benefit,stcpay,tap_benefit,tap_benefitpay|string',
            'otp' => 'required_if:payment_method,benefit,stcpay|string',
            // Tap-specific fields - ONLY secure tokens, NO raw card data
            'tap_token' => 'required_if:payment_method,tap_card,tap_apple_pay,tap_google_pay,tap_benefitpay|string',
            'tap_source' => 'required_if:payment_method,tap_benefit|in:src_bh.benefit',
            'save_card' => 'sometimes|boolean',
            'customer_id' => 'sometimes|string',
            'card_id' => 'sometimes|string',
            'idempotency_key' => 'sometimes|string|max:255',
        ];
    }
}

