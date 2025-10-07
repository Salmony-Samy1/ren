<?php

namespace App\Http\Requests;

class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.ar' => 'required|array',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|boolean',
            'main_service_id' => 'required|exists:main_services,id',
            'questions' => 'nullable|array',
            'questions.*.translations' => 'required|array',
            'questions.*.translations.en' => 'required|array',
            'questions.*.translations.ar' => 'required|array',
            'questions.*.translations.*.label' => 'required|string|max:255',
            'questions.*.translations.*.help_text' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
