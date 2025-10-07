<?php

namespace App\Http\Requests\Admin\Settlements;

use Illuminate\Foundation\Http\FormRequest;

class PartialSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // secured by admin middleware and policies on route
    }

    public function rules(): array
    {
        return [
            'provider_amount' => 'required_without:percentage|numeric|min:0',
            'customer_amount' => 'nullable|numeric|min:0',
            'percentage' => 'required_without:provider_amount|numeric|min:1|max:99',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}

