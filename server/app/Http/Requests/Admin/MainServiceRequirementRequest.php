<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyLegalDocType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MainServiceRequirementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::enum(CompanyLegalDocType::class)],
            'is_required' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Document type is required.',
            'document_type.enum' => 'Invalid document type selected.',
            'is_required.required' => 'Required status must be specified.',
            'is_required.boolean' => 'Required status must be true or false.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'description_en.max' => 'English description cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_type' => 'document type',
            'is_required' => 'required status',
            'description' => 'description',
            'description_en' => 'English description',
        ];
    }
}