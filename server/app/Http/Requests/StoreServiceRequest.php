<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Services\Validation\ServiceCountryValidationService;

class StoreServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],

            // Bilingual fields
            'name' => ['required', function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    if (!isset($value['ar']) || !isset($value['en']) || !is_string($value['ar']) || !is_string($value['en'])) {
                        $fail(__('validation.translations_required_keys', ['attribute' => $attribute]));
                    }
                } elseif (!is_string($value)) {
                    $fail(__('validation.string_or_translations', ['attribute' => $attribute]));
                }
            }],
            'description' => ['nullable', function ($attribute, $value, $fail) {
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
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'place_id' => ['nullable', 'string'],

            // Media uploads (provider can attach on root for service)
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',

            // Pricing & availability (common)
            'country_id' => ['required', 'exists:countries,id'],
            'price_amount' => ['required', 'numeric', 'min:0'],

            // Gender requirement for all services
            'gender_type' => ['required', 'in:male,female,both'],

            // Event specific validation
            'event' => 'nullable|array',
            'event.event_name' => 'required_with:event|string|max:255',
            'event.description' => 'required_with:event|string',
            'event.max_individuals' => 'required_with:event|integer|min:1',
            'event.meeting_point' => 'required_with:event|string',
            'event.age_min' => 'nullable|integer|min:0|max:150',
            'event.age_max' => 'nullable|integer|min:0|max:150|gte:event.age_min',
            'event.gender_type' => 'required_with:event|in:male,female,both',
            'event.hospitality_available' => 'required_with:event|boolean',
            'event.pricing_type' => 'required_with:event|string',
            'event.base_price' => 'required_with:event|numeric|min:0',
            'event.discount_price' => 'nullable|numeric|min:0',
            'event.prices_by_age' => 'nullable|array',
            'event.cancellation_policy' => 'required_with:event|string',
            'event.meeting_point' => 'nullable|string',
            'event.language' => 'required_with:event|in:ar,en,both',
            'event.start_at' => 'required_with:event|date',
            'event.end_at' => 'required_with:event|date|after_or_equal:event.start_at',


            // Property specific validation
            'property' => 'nullable|array', // Property payload is only required when "property" type is sent; enforced by withValidator
            'property.property_name' => 'required_with:property|string|max:255',
            'property.type' => 'required_with:property|string|max:255',
            'property.category' => 'required_with:property|string|max:255',
            'property.city_id' => 'required_with:property|exists:cities,id',
            'property.region_id' => 'nullable|exists:regions,id',
            'property.neigbourhood_id' => 'nullable|exists:neigbourhoods,id',
            'property.direction' => 'nullable|string|max:255',
            'property.images' => 'nullable|array',
            'property.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.videos' => 'nullable|array',
            'property.videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            // Section-specific photos (accept at root or under property.*)
            'bedroom_photos' => 'nullable|array',
            'bedroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'kitchen_photos' => 'nullable|array',
            'kitchen_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pool_photos' => 'nullable|array',
            'pool_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'bathroom_photos' => 'nullable|array',
            'bathroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'living_room_photos' => 'nullable|array',
            'living_room_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.bedroom_photos' => 'nullable|array',
            'property.bedroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.kitchen_photos' => 'nullable|array',
            'property.kitchen_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.pool_photos' => 'nullable|array',
            'property.pool_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.bathroom_photos' => 'nullable|array',
            'property.bathroom_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.living_room_photos' => 'nullable|array',
            'property.living_room_photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'property.unit_code' => 'required_with:property|string|max:255',
            'property.area_sqm' => 'required_with:property|integer|min:0',
            'property.down_payment_percentage' => 'required_with:property|numeric|between:0,99.99',
            'property.is_refundable_insurance' => 'required_with:property|boolean',
            'property.cancellation_policy' => 'required_with:property|string',
            'property.description' => 'required_with:property|string',
            'property.allowed_category' => 'required_with:property|string|max:255',
            'property.room_details' => 'required_with:property|array',
            'property.facilities' => 'required_with:property|array',
            'property.access_instructions' => 'required_with:property|string',
            'property.nightly_price' => 'required_with:property|numeric|min:0',
            'property.max_adults' => 'nullable|integer|min:1',
            'property.max_children' => 'nullable|integer|min:0',
            'property.child_free_until_age' => 'nullable|integer|min:0',
            'property.checkin_time' => 'required_with:property|string|max:255',
            'property.checkout_time' => 'required_with:property|string|max:255',

            // المستندات القانونية
            'property.legal_documents' => 'nullable|array',
            'property.legal_documents.*.document_type' => 'required_with:property.legal_documents|in:ownership_contract,rental_contract,other',
            'property.legal_documents.*.document_name' => 'required_with:property.legal_documents|string|max:255',
            'property.legal_documents.*.file' => 'required_with:property.legal_documents|file|mimes:pdf,jpg,jpeg,png|max:10240',

            'property.bedrooms' => 'required_with:property|array',
            'property.bedrooms.*.bed_type' => 'required|string|max:255',
            'property.bedrooms.*.count' => 'required|integer|min:1',
            'property.bedrooms.*.features' => 'nullable|string',
            'property.bedrooms.*.is_master' => 'nullable|boolean',

            'property.kitchens' => 'required_with:property|array',
            'property.kitchens.*.type' => 'required|string|max:255',
            'property.kitchens.*.features' => 'nullable|string',
            'property.kitchens.*.dining_chairs' => 'nullable|integer|min:0',

            'property.living_rooms' => 'required_with:property|array',
            'property.living_rooms.*.type' => 'required|string|max:255',
            'property.living_rooms.*.capacity' => 'nullable|integer|min:0',

            'property.pools' => 'required_with:property|array',
            'property.pools.*.type' => 'required|string|max:255',
            'property.pools.*.features' => 'nullable|string',
            'property.pools.*.length_m' => 'nullable|numeric|min:0',
            'property.pools.*.width_m'  => 'nullable|numeric|min:0',
            'property.pools.*.depth_m'  => 'nullable|numeric|min:0',
            'property.pools.*.has_heating' => 'nullable|boolean',
            'property.pools.*.has_barrier' => 'nullable|boolean',
            'property.pools.*.has_water_games' => 'nullable|boolean',
            'property.pools.*.is_graduated' => 'nullable|boolean',

            'property.bathrooms' => 'required_with:property|array',
            'property.bathrooms.*.type' => 'required|string|max:255',
            'property.bathrooms.*.count' => 'required|integer|min:1',
            'property.bathrooms.*.features' => 'nullable|string',

            'property.facilities' => 'required_with:property|array',
            'property.facilities.*' => 'integer|exists:facilities,id', // Validates that each facility ID exists


            // === Restaurant specific validation (START OF CORRECTION) ===
            'restaurant' => 'nullable|array',
            'restaurant.description' => ['required_with:restaurant', function ($attribute, $value, $fail) {
                if (is_array($value)) {
                    if (!isset($value['ar']) || !isset($value['en']) || !is_string($value['ar']) || !is_string($value['en'])) {
                        $fail(__('validation.translations_required_keys', ['attribute' => $attribute]));
                    }
                } elseif (!is_string($value)) {
                    $fail(__('validation.string_or_translations', ['attribute' => $attribute]));
                }
            }],
            'restaurant.working_hours' => 'nullable|array',
            'restaurant.available_tables_map' => 'nullable|array',
            'restaurant.daily_available_bookings' => 'nullable|integer|min:0',
            'restaurant.grace_period_minutes' => 'nullable|integer|min:0',
            // === (END OF CORRECTION) ===


            // Legal compliance for catering services - one refund policy page only
            'terms_accepted' => 'required_with:catering|boolean|accepted', // Accepts catering refund policy only
            'legal_page_ids' => 'nullable|array',
            'legal_page_ids.*' => 'exists:legal_pages,id',

            // CateringItem specific validation
            'catering' => 'nullable|array',
            'catering.catering_name' => 'required_with:catering|string|max:255',
            'catering.cuisine_type' => 'required_with:catering|string|max:255',
            'catering.description' => 'required_with:catering|string',
            'catering.min_order_amount' => 'required_with:catering|numeric|min:0',
            'catering.max_order_amount' => 'nullable|numeric|min:0',
            'catering.available_stock' => 'nullable|integer|min:0',
            'catering.preparation_time' => 'nullable|integer|min:0',
            'catering.cancellation_policy' => 'required_with:catering|string',
            'catering.fulfillment_methods' => 'nullable|array',
            'catering.images' => 'nullable|array',
            'catering.videos' => 'nullable|array',
            'catering.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'catering.videos.*' => 'nullable|file|mimes:mp4,mov,ogg|max:20480',
            
            // Catering-specific fields
            'catering_name' => 'nullable|string|max:255',
            'cuisine_type' => 'nullable|string|max:255',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0',
            'preparation_time' => 'nullable|integer|min:0',
            'delivery_available' => 'nullable|boolean',
            'delivery_radius_km' => 'nullable|integer|min:0',
            // Direct catering items structure (from form-data)
            'catering_items' => 'nullable|array',
            'catering_items.*.meal_name' => 'nullable|string|max:255',
            'catering_items.*.price' => 'nullable|numeric|min:0',
            'catering_items.*.servings_count' => 'nullable|integer|min:1',
            'catering_items.*.description' => 'nullable|string',
            'catering_items.*.photos' => 'nullable|array',
            'catering_items.*.photos.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'catering_items.*.availability_stock' => 'nullable|integer|min:0',
            
            // Legacy catering_item structure
            'catering_item' => 'nullable|array',
            'catering_item.packages' => 'nullable|array',
            'catering_item.packages.*.package_name' => 'required_with:catering_item.packages|string|max:255',
            'catering_item.packages.*.price' => 'required_with:catering_item.packages|numeric|min:0',
            'catering_item.packages.*.available_stock' => 'nullable|integer|min:0',
            'catering_item.packages.*.category_id' => 'required_with:catering_item.packages|exists:catering_item_categories,id',
            'catering_item.packages.*.images' => 'nullable|array',
            'catering_item.packages.*.images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'catering_item.available_stock' => 'nullable|array',
            'catering_item.available_stock.*' => 'nullable|integer|min:0',
            'catering_item.packages.*.items' => 'nullable|array',
            'catering_item.packages.*.items.*' => 'string|max:255',
            'catering_item.availability_schedule' => 'nullable|array',
            'catering_item.availability_schedule.*' => 'string|max:32',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function($v){
            $data = $this->all();
            $types = collect(['event','property','restaurant','catering'])
                ->filter(fn($k) => isset($data[$k]) && is_array($data[$k]))
                ->values();

            // Validate that category belongs to the correct main service based on the chosen type
            $typeToMainService = [
                'event' => 1,
                'catering' => 2,
                'restaurant' => 3,
                'property' => 4,
            ];
            if ($types->count() === 1) {
                $chosen = $types->first();
                $catId = (int)($data['category_id'] ?? 0);
                if ($catId > 0) {
                    $cat = \App\Models\Category::find($catId);
                    if (!$cat || (int)$cat->main_service_id !== (int)$typeToMainService[$chosen]) {
                        $v->errors()->add('category_id', __('الفئة المختارة لا تنتمي إلى الخدمة الرئيسية الصحيحة: '.$chosen));
                    }
                }
            }
            if ($types->count() === 0) {
                $v->errors()->add('service_type', __('يجب تحديد نوع خدمة واحد على الأقل (event/property/restaurant/catering).'));
            } elseif ($types->count() > 1) {
                $v->errors()->add('service_type', __('لا يمكن إرسال أكثر من نوع خدمة في نفس الطلب.'));
            }

            // Validate country consistency with legal documents
            if (isset($data['category_id']) && isset($data['country_id']) && auth()->check()) {
                try {
                    $validationService = app(ServiceCountryValidationService::class);
                    $validationService->validateServiceCountry(
                        auth()->id(),
                        (int)$data['category_id'],
                        (int)$data['country_id']
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    foreach ($e->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $v->errors()->add($field, $message);
                        }
                    }
                }
            }
        });
    }
}

