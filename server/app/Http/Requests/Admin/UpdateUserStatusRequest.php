<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,disabled,banned'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower(trim((string) $this->input('status'))),
            ]);
        }
    }
}

