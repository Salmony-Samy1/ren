<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:50000',
            'currency' => 'sometimes|string|in:SAR,BHD',
            'payment_method' => 'required|string|in:tap_card,tap_apple_pay,tap_google_pay,tap_benefit,tap_benefitpay,test',
            
            // Tap-specific fields - ONLY secure tokens, NO raw card data
            'tap_token' => 'required_if:payment_method,tap_card,tap_apple_pay,tap_google_pay,tap_benefitpay|string',
            'tap_source' => 'required_if:payment_method,tap_benefit|in:src_bh.benefit',
            'save_card' => 'sometimes|boolean',
            'customer_id' => 'sometimes|string',
            'card_id' => 'sometimes|string',
            'idempotency_key' => 'sometimes|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'الحد الأدنى للشحن هو 0.01',
            'amount.max' => 'الحد الأقصى للشحن هو 50,000',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير مدعومة',
            'tap_token.required_if' => 'رمز الدفع مطلوب',
            'tap_source.required_if' => 'مصدر الدفع مطلوب',
        ];
    }
}
