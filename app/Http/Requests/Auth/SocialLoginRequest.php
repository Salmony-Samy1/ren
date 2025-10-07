<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'provider' => 'required|in:google,apple,facebook',
            'access_token' => 'required|string',
        ];
    }
}

