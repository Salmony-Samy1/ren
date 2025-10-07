<?php

namespace App\Http\Requests\Admin\Settlements;

use Illuminate\Foundation\Http\FormRequest;

class SettlementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'service_type' => 'nullable|in:event,restaurant,property,catering',
            'provider_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}

