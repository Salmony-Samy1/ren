<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                // السعودية 10 أرقام، البحرين 9 أرقام: نتحقق لاحقاً مع country_code في AuthController إذا لزم
                'regex:/^(\d{9}|\d{10})$/',
                'unique:users,national_id'
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                // السعودية 9 تبدأ بـ5، البحرين 8، ونقبل أيضًا الشكل الدولي +966/+973
                'regex:/^(\+?973\d{8}|\+?9665\d{8}|5\d{8}|\d{8})$/',
                'unique:users,phone'
            ],
            'country_id' => 'required|exists:countries,id',
            'region_id' => 'required|exists:regions,id',
            'neigbourhood_id' => 'required|exists:neigbourhoods,id',
            'hobbies' => 'nullable|array',
            'hobbies.*' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            'gender' => 'required|string|in:male,female',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
