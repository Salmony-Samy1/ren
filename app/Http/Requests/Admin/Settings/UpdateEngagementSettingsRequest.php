<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEngagementSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'review_prompt_delay_minutes' => 'required|integer|min:0|max:10080', // up to 7 days
        ];
    }
}

