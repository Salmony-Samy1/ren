<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

