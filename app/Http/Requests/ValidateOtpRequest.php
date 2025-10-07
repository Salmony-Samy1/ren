<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+?973\d{8}|\+?9665\d{8}|5\d{8}|\d{8})$/',
            ],
            'otp' => 'required|digits:6',
            'country_id' => 'required|exists:countries,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
