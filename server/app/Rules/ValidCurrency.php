<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCurrency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, ['SAR', 'BHD'], true)) {
            $fail(__('العملة يجب أن تكون SAR أو BHD'));
        }
    }
}

