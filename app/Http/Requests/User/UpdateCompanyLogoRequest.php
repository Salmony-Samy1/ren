<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyLogoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'logo' => 'required|image|max:5120', // 5MB
        ];
    }

    public function authorize(): bool
    {
        return auth()->check();
    }
}

