<?php

namespace App\Http\Requests\Gifts;

use Illuminate\Foundation\Http\FormRequest;

class RespondGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:accept,reject',
        ];
    }
}

