<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->type === 'admin'; }

    public function rules(): array
    {
        return [
            'provider_id' => ['required','integer','exists:users,id'],
            'category_id' => ['required','integer','exists:categories,id'],
            'name' => ['required','string','max:255'],
            'address' => ['nullable','string','max:255'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'place_id' => ['nullable','string','max:255'],
            'price_currency_id' => ['nullable','integer','exists:currencies,id'],
            'price_amount' => ['nullable','numeric','min:0'],
            
            'event_name' => ['required','string','max:255'],
            'description' => ['required','string'],
            'language' => ['required','in:ar,en,both'],
            'max_individuals' => ['required','integer','min:1'],
            'start_at' => ['required','date'],
            'end_at' => ['required','date','after_or_equal:start_at'],
            'gender_type' => ['required','in:male,female,both'],
            'hospitality_available' => ['boolean'],
            'price_per_person' => ['required','numeric','min:0'],
            'pricing_type' => ['nullable','string','in:fixed,variable'],
            'base_price' => ['nullable','numeric','min:0'],
            'discount_price' => ['nullable','numeric','min:0'],
            'cancellation_policy' => ['required','string'],
            'meeting_point' => ['nullable','string','max:255'],
            'images' => ['nullable','array'],
            'images.*' => ['file','mimes:jpg,jpeg,png,webp','max:2048'],
            'videos' => ['nullable','array'],
            'videos.*' => ['file','mimetypes:video/mp4,video/quicktime,video/x-msvideo','max:10240'],
        ];
    }
}

