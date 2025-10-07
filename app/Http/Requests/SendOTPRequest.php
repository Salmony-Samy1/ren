<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOTPRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:20',
                // نقبل 8 للبحرين، 9 تبدأ بـ5 للسعودية، أو دولي يبدأ بـ +966/+973
                'regex:/^(\+?973\d{8}|\+?9665\d{8}|5\d{8}|\d{8})$/',
            ],
            'country_id' => 'required|exists:countries,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
