<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled in the controller or a policy,
        // so we can return true here.
        // يتم التعامل مع الصلاحيات في المتحكم أو البوليسي، لذا يمكننا إرجاع true هنا
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All rules use 'sometimes' to allow for partial updates (PATCH behavior).
     * Only the fields present in the request will be validated.
     *
     * تستخدم جميع القواعد 'sometimes' للسماح بالتحديثات الجزئية.
     * سيتم التحقق فقط من الحقول الموجودة في الطلب.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],

            // Bilingual fields
            'name' => ['sometimes', 'required', function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    if (!isset($value['ar']) || !isset($value['en']) || !is_string($value['ar']) || !is_string($value['en'])) {
                        $fail(__('validation.translations_required_keys', ['attribute' => $attribute]));
                    }
                } elseif (!is_string($value)) {
                    $fail(__('validation.string_or_translations', ['attribute' => $attribute]));
                }
            }],
            'description' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($value === null) return;
                if (is_array($value)) {
                    if (!isset($value['ar']) || !isset($value['en']) || !is_string($value['ar']) || !is_string($value['en'])) {
                        $fail(__('validation.translations_required_keys', ['attribute' => $attribute]));
                    }
                } elseif (!is_string($value)) {
                    $fail(__('validation.string_or_translations', ['attribute' => $attribute]));
                }
            }],

            // Location fields
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'latitude' => ['sometimes', 'required', 'numeric'],
            'longitude' => ['sometimes', 'required', 'numeric'],
            'place_id' => ['sometimes', 'required', 'string'],

            // Pricing & availability (common)
            'price_currency_id' => ['sometimes', 'required', 'exists:countries,id'],
            'country_id' => ['sometimes', 'required', 'exists:countries,id'],
            'price_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'available_from' => ['sometimes', 'nullable', 'date'],
            'available_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:available_from'],

            // Event specific validation
            'images' => ['sometimes', 'nullable', 'array'],
            'event' => ['sometimes', 'nullable', 'array'],
            'event.event_name' => ['sometimes', 'required_with:event', 'string', 'max:255'],
            'event.description' => ['sometimes', 'required_with:event', 'string'],
            'event.max_individuals' => ['sometimes', 'required_with:event', 'integer', 'min:1'],
            'event.meeting_point' => 'required_with:event|string',
            'event.start_date' => ['sometimes', 'required_with:event', 'date'],
            'event.end_date' => ['sometimes', 'required_with:event', 'date', 'after_or_equal:event.start_date'],
            'event.gender_type' => ['sometimes', 'required_with:event', 'in:male,female,both'],
            'event.hospitality_available' => ['sometimes', 'required_with:event', 'boolean'],
            'event.pricing_type' => ['sometimes', 'required_with:event', 'string'],
            'event.base_price' => ['sometimes', 'required_with:event', 'numeric', 'min:0'],
            'event.discount_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'event.prices_by_age' => ['sometimes', 'nullable', 'array'],
            'event.cancellation_policy' => ['sometimes', 'required_with:event', 'string'],
            'event.meeting_point' => ['sometimes', 'nullable', 'string'],

            // Property specific validation
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*' => ['nullable','file','image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'property' => ['sometimes', 'nullable', 'array'],
            'property.images' => ['sometimes','nullable','array'],
            'property.images.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.videos' => ['sometimes','nullable','array'],
            'property.videos.*' => ['nullable','file','mimetypes:video/mp4,video/quicktime,video/ogg','max:20480'],
            'bedroom_photos' => ['sometimes','nullable','array'],
            'bedroom_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'kitchen_photos' => ['sometimes','nullable','array'],
            'kitchen_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'pool_photos' => ['sometimes','nullable','array'],
            'pool_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'bathroom_photos' => ['sometimes','nullable','array'],
            'bathroom_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'living_room_photos' => ['sometimes','nullable','array'],
            'living_room_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.bedroom_photos' => ['sometimes','nullable','array'],
            'property.bedroom_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.kitchen_photos' => ['sometimes','nullable','array'],
            'property.kitchen_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.pool_photos' => ['sometimes','nullable','array'],
            'property.pool_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.bathroom_photos' => ['sometimes','nullable','array'],
            'property.bathroom_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.living_room_photos' => ['sometimes','nullable','array'],
            'property.living_room_photos.*' => ['nullable','file','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'property.property_name' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.type' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.category' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.unit_code' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.area_sqm' => ['sometimes', 'required_with:property', 'integer', 'min:0'],
            'property.down_payment_percentage' => ['sometimes', 'required_with:property', 'numeric', 'between:0,99.99'],
            'property.is_refundable_insurance' => ['sometimes', 'required_with:property', 'boolean'],
            'property.cancellation_policy' => ['sometimes', 'required_with:property', 'string'],
            'property.description' => ['sometimes', 'required_with:property', 'string'],
            'property.allowed_category' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.access_instructions' => ['sometimes', 'required_with:property', 'string'],
            'property.nightly_price' => ['sometimes', 'required_with:property', 'numeric', 'min:0'],
            'property.checkin_time' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.checkout_time' => ['sometimes', 'required_with:property', 'string', 'max:255'],
            'property.room_details' => ['sometimes', 'required_with:property', 'array'],
            'property.bedrooms' => ['sometimes', 'required_with:property', 'array'],
                'property.bedrooms.*.is_master' => ['sometimes','nullable','boolean'],
            'property.kitchens' => ['sometimes', 'required_with:property', 'array'],
                'property.kitchens.*.dining_chairs' => ['sometimes','nullable','integer','min:0'],
            'property.living_rooms' => ['sometimes', 'required_with:property', 'array'],
                'property.living_rooms.*.type' => ['sometimes','required','string','max:255'],
            'property.pools' => ['sometimes', 'required_with:property', 'array'],
                'property.pools.*.length_m' => ['sometimes','nullable','numeric','min:0'],
                'property.pools.*.width_m'  => ['sometimes','nullable','numeric','min:0'],
                'property.pools.*.depth_m'  => ['sometimes','nullable','numeric','min:0'],
                'property.pools.*.has_heating' => ['sometimes','nullable','boolean'],
                'property.pools.*.has_barrier' => ['sometimes','nullable','boolean'],
                'property.pools.*.has_water_games' => ['sometimes','nullable','boolean'],
            'property.bathrooms' => ['sometimes', 'required_with:property', 'array'],
            'property.facilities' => ['sometimes', 'required_with:property', 'array'],
            'property.facilities.*' => ['integer', 'exists:facilities,id'],

            // Restaurant specific validation
            'restaurant' => ['sometimes', 'nullable', 'array'],
            'restaurant.description' => ['sometimes', 'required_with:restaurant', 'string'],
            'restaurant.working_hours' => ['sometimes', 'nullable', 'array'],
            'restaurant.available_tables_map' => ['sometimes', 'nullable', 'array'],
            'restaurant.daily_available_bookings' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'restaurant.grace_period_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],

            // Catering (main) validation
            'catering' => ['sometimes', 'nullable', 'array'],
            'catering.images' => ['sometimes', 'nullable', 'array'],
            'catering.images.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'catering.videos' => ['sometimes', 'nullable', 'array'],
            'catering.videos.*' => ['file', 'mimetypes:video/mp4,video/quicktime,video/ogg', 'max:20480'],
            
            // Catering-specific fields
            'catering_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cuisine_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'preparation_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'delivery_available' => ['sometimes', 'nullable', 'boolean'],
            'delivery_radius_km' => ['sometimes', 'nullable', 'integer', 'min:0'],

            // CateringItem specific validation
            'catering_item' => ['sometimes', 'nullable', 'array'],
            'catering_item.packages' => ['sometimes', 'nullable', 'array'],
            'catering_item.availability_schedule' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

