<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'app_logo_url' => 'nullable|url',
            'gift_background_url' => 'nullable|url',
        ];
    }
}

