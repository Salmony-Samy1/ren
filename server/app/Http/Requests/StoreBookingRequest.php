<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Service; 

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // التحقق من أن المستخدم مصادق عليه
        if (!auth()->check()) {
            return false;
        }
        
        // التحقق من أن المستخدم ليس من نوع admin يحاول حجز خدمة
        $user = auth()->user();
        if ($user->type === 'admin') {
            return false; // Admins لا يمكنهم حجز خدمات
        }
        
        return true;
    }

    public function rules(): array
    {
        $service = Service::find($this->input('service_id'));

        $rules = [
            'service_id'       => 'required|integer|exists:services,id',
            'start_date'       => 'sometimes|date|after_or_equal:now',
            'end_date'         => 'sometimes|date|after_or_equal:start_date',
            'booking_details'  => 'required|array',
            'payment_method'   => 'required|string|in:wallet,apple_pay,visa,mada,samsung_pay,benefit,stcpay,tap_card,tap_benefit,tap_apple_pay,tap_google_pay,tap_benefitpay',
            'idempotency_key'  => 'sometimes|string|max:100',
            'coupon_code'      => 'sometimes|string|max:50',
            'points_to_use'    => 'sometimes|integer|min:1',
        ];

        if ($service) {
            if ($service->restaurant) {
                $rules = array_merge($rules, [
                    'booking_details.number_of_people' => 'required|integer|min:1',
                    'booking_details.booking_time'     => 'required|date_format:H:i',
                    'booking_details.table_id'         => 'required|integer|exists:restaurant_tables,id',
                    'booking_details.notes'            => 'nullable|string|max:1000',
                    'booking_details.menu_items'       => 'sometimes|array',
                    'booking_details.menu_items.*.item_id' => 'required_with:booking_details.menu_items|integer|exists:restaurant_menu_items,id',
                    'booking_details.menu_items.*.quantity' => 'required_with:booking_details.menu_items|integer|min:1',
                ]);
            } elseif ($service->catering) {
                $rules = array_merge($rules, [
                    'booking_details.number_of_items' => 'required|integer|min:1',
                    'booking_details.add_ons'         => 'sometimes|array',
                    'booking_details.add_ons.*.id'    => 'required_with:booking_details.add_ons|integer|exists:catering_items,id',
                    'booking_details.add_ons.*.qty' => 'required_with:booking_details.add_ons|integer|min:1',
                    'fulfillment_method'              => 'required|in:delivery,pickup,on_site',
                    'address_id'                      => 'required_if:fulfillment_method,delivery|integer|exists:user_addresses,id',
                ]);
            } elseif ($service->property) {
                $rules = array_merge($rules, [
                    'booking_details.adults'          => 'required|integer|min:1',
                    'booking_details.children'        => 'sometimes|integer|min:0',
                    'booking_details.children_ages'   => 'sometimes|array',
                ]);
            } elseif ($service->event) {
                $rules = array_merge($rules, [
                    'booking_details.number_of_people' => 'required|integer|min:1',
                ]);
            }
        }

        // إضافة قواعد الدفع حسب طريقة الدفع المختارة
        $paymentMethod = $this->input('payment_method');
        if ($paymentMethod) {
            $paymentRules = $this->getPaymentRules($paymentMethod);
            $rules = array_merge($rules, $paymentRules);
        }

        return $rules;
    }

    /**
     * قواعد التحقق من الدفع حسب طريقة الدفع
     */
    private function getPaymentRules(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'tap_card', 'tap_apple_pay', 'tap_google_pay', 'tap_benefitpay' => [
                'tap_token' => 'required|string',
                'save_card' => 'sometimes|boolean',
                'customer_id' => 'sometimes|string',
                'card_id' => 'sometimes|string',
            ],
            'tap_benefit' => [
                'tap_source' => 'required|in:src_bh.benefit',
                'phone_number' => 'required|string',
            ],
            'apple_pay' => [
                'apple_pay_token' => 'required|string',
            ],
            'samsung_pay' => [
                'samsung_pay_token' => 'required|string',
            ],
            'benefit', 'stcpay' => [
                'phone_number' => 'required|string',
                'otp' => 'required|string',
            ],
            default => []
        };
    }
}