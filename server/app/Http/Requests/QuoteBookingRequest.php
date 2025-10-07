<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Single-item quote (required if orders is not provided)
            'service_id' => 'required_without:orders|exists:services,id',
            'start_date' => 'required_without:orders|date|after_or_equal:now',
            'end_date' => 'required_without:orders|date|after_or_equal:start_date',
            'booking_details' => 'required_without:orders|array',

            // Common booking_details for single quote
            'booking_details.adults' => 'sometimes|integer|min:0',
            'booking_details.children' => 'sometimes|integer|min:0',
            'booking_details.children_ages' => 'sometimes|array',
            'booking_details.children_ages.*' => 'integer|min:0',
            // Restaurant: specific table row
            'booking_details.table_id' => 'sometimes|integer|exists:restaurant_tables,id',
            'booking_details.number_of_people' => 'sometimes|integer|min:1',
            // Restaurant menu (single quote)
            'booking_details.menu_items' => 'sometimes|array',
            'booking_details.menu_items.*.item_id' => 'required_with:booking_details.menu_items|integer|exists:restaurant_menu_items,id',
            'booking_details.menu_items.*.quantity' => 'required_with:booking_details.menu_items|integer|min:1',
            // Catering specific
            'booking_details.number_of_items' => 'sometimes|integer|min:1',
            'booking_details.add_ons' => 'sometimes|array',
            'booking_details.add_ons.*.id' => 'required_with:booking_details.add_ons|integer|exists:catering_items,id',
            'booking_details.add_ons.*.qty' => 'required_with:booking_details.add_ons|integer|min:1',
            // Property specific
            'booking_details.number_of_nights' => 'sometimes|integer|min:1',
            // Optional: attach a draft order to preview combined total
            'order_id' => 'sometimes|integer|exists:orders,id',

            // Multi-item quote (orders array)
            'orders' => 'sometimes|array|min:1',
            'orders.*.service_id' => 'required_with:orders|integer|exists:services,id',
            'orders.*.start_date' => 'required_with:orders|date|after_or_equal:now',
            'orders.*.end_date' => 'required_with:orders|date|after_or_equal:orders.*.start_date',
            'orders.*.booking_details' => 'required_with:orders|array',
            // Catering
            'orders.*.booking_details.number_of_items' => 'sometimes|integer|min:1',
            'orders.*.booking_details.add_ons' => 'sometimes|array',
            'orders.*.booking_details.add_ons.*.id' => 'required_with:orders.*.booking_details.add_ons|integer|exists:catering_items,id',
            'orders.*.booking_details.add_ons.*.qty' => 'required_with:orders.*.booking_details.add_ons|integer|min:1',
            // Restaurant
            'orders.*.booking_details.table_id' => 'sometimes|integer|exists:restaurant_tables,id',
            'orders.*.booking_details.number_of_people' => 'sometimes|integer|min:1',
            'orders.*.booking_details.menu_items' => 'sometimes|array',
            'orders.*.booking_details.menu_items.*.item_id' => 'required_with:orders.*.booking_details.menu_items|integer|exists:restaurant_menu_items,id',
            'orders.*.booking_details.menu_items.*.quantity' => 'required_with:orders.*.booking_details.menu_items|integer|min:1',
            // Property
            'orders.*.booking_details.number_of_nights' => 'sometimes|integer|min:1',
            // Optional per-order draft order id
            'orders.*.order_id' => 'sometimes|integer|exists:orders,id',

            // Optional coupon/points for preview (applied across the total in multi mode)
            'coupon_code' => 'sometimes|string|max:50',
            'points_to_use' => 'sometimes|integer|min:1',
        ];
    }
}

