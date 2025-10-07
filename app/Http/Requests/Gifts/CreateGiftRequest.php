<?php

namespace App\Http\Requests\Gifts;

use Illuminate\Foundation\Http\FormRequest;

class CreateGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_public_id' => 'required|string|exists:users,public_id',
            'type' => 'required|in:direct,package,voucher',
            'amount' => 'required_if:type,direct|nullable|numeric|min:0.01',
            'gift_package_id' => 'required_if:type,package|nullable|exists:gift_packages,id',
            'service_id' => 'required_if:type,voucher|nullable|exists:services,id',
            'message' => 'nullable|string|max:255',
            'currency' => 'sometimes|string|in:SAR,BHD',
        ];
    }
}

