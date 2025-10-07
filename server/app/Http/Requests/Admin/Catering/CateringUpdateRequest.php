<?php

namespace App\Http\Requests\Admin\Catering;

use Illuminate\Foundation\Http\FormRequest;

class CateringUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()?->type === 'admin';
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes','integer','exists:categories,id'],
            'name' => ['sometimes','string','max:255'],
            'address' => ['sometimes','nullable','string','max:255'],
            'latitude' => ['sometimes','nullable','numeric','between:-90,90'],
            'longitude' => ['sometimes','nullable','numeric','between:-180,180'],
            'place_id' => ['sometimes','nullable','string','max:255'],
            'price_currency_id' => ['sometimes','nullable','integer','exists:currencies,id'],
            'price_amount' => ['sometimes','nullable','numeric','min:0'],

            'description' => ['sometimes','nullable','string'],
            'available_stock' => ['sometimes','nullable','integer','min:0'],
            'fulfillment_methods' => ['sometimes','nullable','array'],
            
            // Catering-specific fields
            'catering_name' => ['sometimes','nullable','string','max:255'],
            'cuisine_type' => ['sometimes','nullable','string','max:255'],
            'min_order_amount' => ['sometimes','nullable','numeric','min:0'],
            'max_order_amount' => ['sometimes','nullable','numeric','min:0'],
            'preparation_time' => ['sometimes','nullable','integer','min:0'],
            'delivery_available' => ['sometimes','nullable','boolean'],
            'delivery_radius_km' => ['sometimes','nullable','integer','min:0'],

            // approvals
            'is_approved' => ['sometimes','boolean'],

            // add-ons packages optional
            'catering_item' => ['sometimes','nullable','array'],
            'catering_item.availability_schedule' => ['sometimes','nullable','array'],
            'catering_item.packages' => ['sometimes','nullable','array'],
            'catering_item.packages.*.package_name' => ['required_with:catering_item.packages','string','max:255'],
            'catering_item.packages.*.price' => ['required_with:catering_item.packages','numeric','min:0'],
            'catering_item.packages.*.available_stock' => ['nullable','integer','min:0'],
            'catering_item.packages.*.items' => ['nullable','array'],

            // Media
            'images' => ['sometimes','nullable','array'],
            'images.*' => ['file','mimes:jpg,jpeg,png,webp','max:2048'],
            'videos' => ['sometimes','nullable','array'],
            'videos.*' => ['file','mimetypes:video/mp4,video/quicktime,video/x-msvideo','max:10240'],
        ];
    }
}

