<?php

namespace App\Http\Requests;

use App\Enums\CommissionTypes;

class SetCommissionRequest extends FormRequest
{
    public function rules(): array
    {
        $commission = ['required', 'numeric', 'min:0'];
        if($this->type === CommissionTypes::percentage){
            $commission[] = 'max:100';
        }
        return [
            'commission' => $commission,
            'type' => 'required|in:' . implode(',', CommissionTypes::cases())
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
