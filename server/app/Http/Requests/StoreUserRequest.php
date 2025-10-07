<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        $password = 'nullable';
        if($this->method() == 'POST'){
            $password = 'required';
        }
        return [
            'email' => ['required', 'email', 'max:254'],
            'password' => [
                $password,
                'confirmed',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            'phone' => [
                'required',
                'digits:8',
                'regex:/^(3\d{7}|66\d{6}|1\d{7})$/',
                'unique:users,phone'
            ],
            'country_id' => 'required|exists:countries,id',
            'full_name' => ['string', 'required', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
