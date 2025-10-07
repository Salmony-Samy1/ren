<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'public_id' => 'required|string|exists:users,public_id',
            'currency' => 'sometimes|string|in:SAR,BHD',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
