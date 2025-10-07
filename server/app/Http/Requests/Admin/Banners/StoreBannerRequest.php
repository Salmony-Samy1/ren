<?php

namespace App\Http\Requests\Admin\Banners;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'image_url' => 'required|url',
            'link_url' => 'nullable|url',
            'placement' => 'required|string|max:100',
            'active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ];
    }
}

