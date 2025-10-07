<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->type === 'admin'; }

    public function rules(): array
    {
        return [
            'service_id' => ['sometimes','exists:services,id'],
            'event_name' => ['sometimes','string','max:255'],
            'description' => ['sometimes','string'],
            'language' => ['sometimes','in:ar,en,both'],
            'max_individuals' => ['sometimes','integer','min:1'],
            'start_at' => ['sometimes','date'],
            'end_at' => ['sometimes','date','after_or_equal:start_at'],
            'gender_type' => ['sometimes','in:male,female,both'],
            'hospitality_available' => ['sometimes','boolean'],
            'price_per_person' => ['sometimes','numeric','min:0'],
            'price_currency_id' => ['sometimes','exists:currencies,id'],
            'cancellation_policy' => ['sometimes','string'],
            'meeting_point' => ['sometimes','nullable','string','max:255'],
            'images' => ['nullable','array'],
            'images.*' => ['file','mimes:jpg,jpeg,png,webp','max:2048'],
            'videos' => ['nullable','array'],
            'videos.*' => ['file','mimetypes:video/mp4,video/quicktime,video/x-msvideo','max:10240'],
        ];
    }
}

