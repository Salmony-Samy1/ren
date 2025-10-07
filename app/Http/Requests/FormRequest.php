<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

class FormRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories'],
            'type' => ['required'],
            'required' => ['boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
