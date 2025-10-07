<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderReelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'main_service_id' => 'required|integer|exists:main_services,id',
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm|max:307200',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4096',
        ];
    }

    public function authorize(): bool
    {
        return in_array($this->user()->type, ['provider', 'company']);

    }
}

